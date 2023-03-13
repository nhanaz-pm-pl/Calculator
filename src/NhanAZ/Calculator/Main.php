<?php

declare(strict_types=1);

namespace NhanAZ\Calculator;

use muqsit\arithmexp\Parser;
use NhanAZ\libBedrock\Sounder;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

class Main extends PluginBase {

	protected function onEnable(): void {
		$this->saveDefaultConfig();
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
		if ($command->getName() === "calculator" && count($args) >= 1) {
			$prefix = $this->getConfig()->get("prefix");
			try {
				$parser = Parser::createDefault();
				$expression = implode(" ", $args);
				$expression = $parser->parse($expression);
				$result = $expression->evaluate();
				if ($this->getConfig()->get("showDataType")) {
					$result = gettype($result) . "({$result})";
				}
				$result = str_replace(["{prefix}", "{result}"], [$prefix, $result], $this->getConfig()->get("result"));
				$sender->sendMessage(TextFormat::colorize($result));
				$this->getConfig()->get("playSound") ? Sounder::play($sender, "mob.villager.yes") : '';
			} catch (\Throwable $e) {
				$error = str_replace(["{prefix}", "{error}"], [$prefix, $e->getMessage()], $this->getConfig()->get("error"));
				$sender->sendMessage(TextFormat::colorize($error));
				$this->getConfig()->get("playSound") ? Sounder::play($sender, "mob.villager.no") : '';
			}
			return true;
		}
		return false;
	}
}
