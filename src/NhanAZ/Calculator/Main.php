<?php

declare(strict_types=1);

namespace NhanAZ\Calculator;

use NhanAZ\libBedrock\Sounder;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use Throwable;

class Main extends PluginBase implements Listener {

	private ExpressionCalculator $calculator;

	/** @var array<string, CalculatorSession> */
	private array $variableSessions = [];

	protected function onEnable(): void {
		$this->calculator = new ExpressionCalculator();
		$this->saveDefaultConfig();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	protected function onDisable(): void {
		$this->variableSessions = [];
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
		if ($command->getName() !== "calculator") {
			return false;
		}

		if (count($args) === 0) {
			return false;
		}

		try {
			$request = $this->calculator->createRequest($args);
			$expression = $this->calculator->parse($request->expression);
			$values = $this->calculator->resolveProvidedValues($request->rawValues);
			$variables = $this->calculator->extractVariables($request->expression);
			$missingVariables = array_values(array_diff($variables, array_keys($values)));

			if (count($missingVariables) > 0) {
				if (!($sender instanceof Player)) {
					$this->sendError($sender, "Variables require a player to answer in chat. Use --variable=value.");
					return true;
				}

				$sessionId = $this->getSessionId($sender);
				if (isset($this->variableSessions[$sessionId])) {
					$this->sendError($sender, "You already have a calculator session in progress. Type cancel to cancel it.");
					return true;
				}

				$this->variableSessions[$sessionId] = new CalculatorSession(
					$sender,
					$expression,
					$variables,
					$missingVariables,
					$values,
				);
				$this->scheduleSessionTimeout($sessionId);
				$this->askForNextVariable($sessionId);
				return true;
			}

			$result = $this->calculator->evaluate($expression, $values);
			$this->sendResult($sender, $result, $this->getUsedVariableValues($variables, $values));
		} catch (Throwable $e) {
			$this->sendError($sender, $e->getMessage());
		}

		return true;
	}

	public function onPlayerChat(PlayerChatEvent $event): void {
		$player = $event->getPlayer();
		$sessionId = $this->getSessionId($player);
		$session = $this->variableSessions[$sessionId] ?? null;
		if ($session === null) {
			return;
		}

		$event->setCancelled();
		$message = trim($event->getMessage());
		if (strtolower($message) === "cancel") {
			unset($this->variableSessions[$sessionId]);
			$this->sendConfiguredMessage($player, "variableCancelled", "{prefix} &cCalculator session cancelled.");
			return;
		}

		$variable = $session->getCurrentVariable();
		try {
			$value = $this->calculator->evaluateValue($message, $session->getValues());
		} catch (Throwable $e) {
			$this->sendConfiguredMessage($player, "variableInvalid", "{prefix} &cInvalid value for &b{variable}&c: {error}", [
				"variable" => $variable,
				"error" => $e->getMessage(),
			]);
			return;
		}

		$session->setCurrentValue($value);
		if (!$session->isComplete()) {
			$this->askForNextVariable($sessionId);
			return;
		}

		$values = $session->getValues();
		try {
			$result = $this->calculator->evaluate($session->getExpression(), $values);
		} catch (Throwable $e) {
			unset($this->variableSessions[$sessionId]);
			$this->sendError($player, $e->getMessage());
			return;
		}

		$expressionVariables = $session->getExpressionVariables();
		unset($this->variableSessions[$sessionId]);
		$this->sendResult($player, $result, $this->getUsedVariableValues($expressionVariables, $values));
	}

	public function onPlayerQuit(PlayerQuitEvent $event): void {
		unset($this->variableSessions[$this->getSessionId($event->getPlayer())]);
	}

	private function scheduleSessionTimeout(string $sessionId): void {
		$timeoutSeconds = max(1, (int) $this->getConfig()->get("variableTimeoutSeconds", 60));
		$this->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($sessionId): void {
			$session = $this->variableSessions[$sessionId] ?? null;
			if ($session === null) {
				return;
			}

			unset($this->variableSessions[$sessionId]);
			$player = $session->getPlayer();
			if ($player->isConnected()) {
				$this->sendConfiguredMessage($player, "variableTimedOut", "{prefix} &cCalculator session timed out.");
			}
		}), $timeoutSeconds * 20);
	}

	private function askForNextVariable(string $sessionId): void {
		$session = $this->variableSessions[$sessionId];
		$this->sendConfiguredMessage($session->getPlayer(), "variablePrompt", "{prefix} &eEnter a value for variable &b{variable}&e. Type &bcancel&e to cancel.", [
			"variable" => $session->getCurrentVariable(),
		]);
	}

	/** @param array<string, int|float|bool> $variables */
	private function sendResult(CommandSender $sender, mixed $result, array $variables = []): void {
		if ((bool) $this->getConfig()->get("showDataType", true)) {
			$result = gettype($result) . "({$result})";
		}
		if (count($variables) > 0) {
			$result .= " where " . $this->formatVariables($variables);
		}

		$this->sendConfiguredMessage($sender, "result", "{prefix} &aResult: &b{result}", [
			"result" => (string) $result,
		]);
		$this->playSound($sender, true);
	}

	/**
	 * @param list<string> $variables
	 * @param array<string, int|float|bool> $values
	 * @return array<string, int|float|bool>
	 */
	private function getUsedVariableValues(array $variables, array $values): array {
		$usedValues = [];
		foreach ($variables as $variable) {
			if (array_key_exists($variable, $values)) {
				$usedValues[$variable] = $values[$variable];
			}
		}
		return $usedValues;
	}

	/** @param array<string, int|float|bool> $variables */
	private function formatVariables(array $variables): string {
		$formatted = [];
		foreach ($variables as $name => $value) {
			$formatted[] = $name . " = " . (is_bool($value) ? ($value ? "true" : "false") : (string) $value);
		}
		return implode(", ", $formatted);
	}

	private function sendError(CommandSender $sender, string $error): void {
		$this->sendConfiguredMessage($sender, "error", "{prefix} &cError: {error}", [
			"error" => $error,
		]);
		$this->playSound($sender, false);
	}

	/** @param array<string, string> $values */
	private function sendConfiguredMessage(CommandSender $sender, string $configKey, string $fallback, array $values = []): void {
		$message = (string) $this->getConfig()->get($configKey, $fallback);
		$replacements = ["{prefix}" => (string) $this->getConfig()->get("prefix", "&f[&6Calculator&f]&r")];
		foreach ($values as $key => $value) {
			$replacements["{{$key}}"] = $value;
		}
		$sender->sendMessage(TextFormat::colorize(strtr($message, $replacements)));
	}

	private function playSound(CommandSender $sender, bool $success): void {
		if ((bool) $this->getConfig()->get("playSound", true)) {
			Sounder::play($sender, $success ? "mob.villager.yes" : "mob.villager.no");
		}
	}

	private function getSessionId(Player $player): string {
		return $player->getUniqueId()->toString();
	}
}
