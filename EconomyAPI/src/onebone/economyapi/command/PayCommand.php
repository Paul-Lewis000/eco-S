<?php

namespace onebone\economyapi\command;

use onebone\economyapi\EconomyAPI;
use onebone\economyapi\event\money\PayMoneyEvent;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class PayCommand extends PluginCommand {
	private $plugin;

	public function __construct(EconomyAPI $plugin) {
		$desc = $plugin->getCommandMessage("pay");
		parent::__construct("pay", $plugin);
		$this->setDescription($desc["description"]);
		$this->setUsage($desc["usage"]);

		$this->setPermission("economyapi.command.pay");
	}

	public function _execute(CommandSender $sender, string $label, array $params): bool {
		if (!$this->testPermission($sender)) {
			return false;
		}

		if (!$sender instanceof Player) {
			$sender->sendMessage(TextFormat::RED . "Please run this command in-game.");
			return true;
		}

		$player = array_shift($params);
		$amount = array_shift($params);

		if (!is_numeric($amount)) {
			$sender->sendMessage(TextFormat::RED . "Usage: " . $this->getUsage());
			return true;
		}

		if (($p = $this->plugin->getServer()->getPlayer($player)) instanceof Player) {
			$player = $p->getName();
		}

		if (!$p instanceof Player and $this->plugin->getConfig()->get("allow-pay-offline", true) === false) {
			$sender->sendMessage($this->plugin->getMessage("player-not-connected", [$player], $sender->getName()));
			return true;
		}

		if (!$this->plugin->accountExists($player)) {
			$sender->sendMessage($this->plugin->getMessage("player-never-connected", [$player], $sender->getName()));
			return true;
		}

		$this->plugin->getServer()->getPluginManager()->callEvent($ev = new PayMoneyEvent($this->plugin, $sender->getName(), $player, $amount));

		$result = EconomyAPI::RET_CANCELLED;
		if (!$ev->isCancelled()) {
			$result = $this->plugin->reduceMoney($sender, $amount);
		}

		if ($result === EconomyAPI::RET_SUCCESS) {
			$this->plugin->addMoney($player, $amount, true);

			$sender->sendMessage($this->plugin->getMessage("pay-success", [$amount, $player], $sender->getName()));
			if ($p instanceof Player) {
				$p->sendMessage($this->plugin->getMessage("money-paid", [$sender->getName(), $amount], $sender->getName()));
			}
		} else {
			$sender->sendMessage($this->plugin->getMessage("pay-failed", [$player, $amount], $sender->getName()));
		}
		return true;
	}
}

