<?php

declare(strict_types=1);

namespace NhanAZ\Calculator;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase {

	const PREFIX = "§f[§bCalculator§f]§r ";

	protected function onEnable(): void {
		$this->getLogger()->emergency("Absolutely do not use this plugin in production!");
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
		if ($command->getName() === "calculator" && count($args) >= 1) {
			$hanlder = implode("", $args);
			var_dump($hanlder);
			$this->getLogger()->alert("Executing PHP: $hanlder");
			try {
				$rerult = eval("return $hanlder;");
				if (is_numeric($rerult)) {
					$sender->sendMessage(self::PREFIX . "§aResult: " . number_format($rerult, 2));
				}
			} catch (\Throwable $e) {
				$sender->sendMessage(self::PREFIX . "§cError: " . $e->getMessage());
				return true;
			}
			return true;
		}
		return false;
	}
}
