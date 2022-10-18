<?php

declare(strict_types=1);

namespace NhanAZ\Calculator;

use muqsit\arithmexp\Parser;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use NhanAZ\libBedrock\libBedrock;

class Main extends PluginBase {

	protected function onEnable(): void {
		$this->saveDefaultConfig();
		libBedrock::checkConfigVersion($this, $this->getConfig(), "configVersion", $this->getDescription()->getVersion());
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
		if ($command->getName() === "calculator" && count($args) >= 1) {
			$prefix = $this->getConfig()->get("prefix");
			try {
				$parser = Parser::createDefault();
				$expression = $parser->parse(implode("", $args));
				$result = $expression->evaluate();
				if ($this->getConfig()->get("showDataType")) {
					$result = gettype($result) . "({$result})";
				}
				$result = str_replace(["{prefix}", "{result}"], [$prefix, $result], $this->getConfig()->get("result"));
				$sender->sendMessage(TextFormat::colorize($result));
				libBedrock::playSound($sender, "mob.villager.yes", $this->getConfig()->get("playSound"));
			} catch (\Throwable $e) {
				$error = str_replace(["{prefix}", "{error}"], [$prefix, $e->getMessage()], $this->getConfig()->get("error"));
				$sender->sendMessage(TextFormat::colorize($error));
				libBedrock::playSound($sender, "mob.villager.no", $this->getConfig()->get("playSound"));
			}
			return true;
		}
		return false;
	}
}
