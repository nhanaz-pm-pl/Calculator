<?php

declare(strict_types=1);

namespace NhanAZ\Calculator;

use muqsit\arithmexp\Parser;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

class Main extends PluginBase {

	protected function onEnable(): void {
		$this->saveDefaultConfig();
	}

	private function playSound($player, string $soundName): void {
		if ($this->getConfig()->get("playSound")) {
			if ($player instanceof Player) {
				$playerPos = $player->getPosition();
				$player->getNetworkSession()->sendDataPacket(PlaySoundPacket::create($soundName, $playerPos->getX(), $playerPos->getY(), $playerPos->getZ(), 1, 1));
			}
		}
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
		if ($command->getName() === "calculator" && count($args) >= 1) {
			$prefix = $this->getConfig()->get("prefix");
			try {
				$parser = Parser::createDefault();
				$expression = $parser->parse(implode("", $args));
				$expressionEvaluate = $expression->evaluate();
				$result = str_replace(["{prefix}", "{result}"], [$prefix, gettype($expressionEvaluate) . "({$expressionEvaluate})"], $this->getConfig()->get("result"));
				$sender->sendMessage(TextFormat::colorize($result));
				$this->playSound($sender, "mob.villager.yes");
			} catch (\Throwable $e) {
				$error = str_replace(["{prefix}", "{error}"], [$prefix, $e->getMessage()], $this->getConfig()->get("error"));
				$sender->sendMessage(TextFormat::colorize($error));
				$this->playSound($sender, "mob.villager.no");
			}
			return true;
		}
		return false;
	}
}
