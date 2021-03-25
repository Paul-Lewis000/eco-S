<?php

/*
 * EconomyS, the massive economy plugin with many features for PocketMine-MP
 * Copyright (C) 2013-2021  onebone <me@onebone.me>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace onebone\economyland\command;

use onebone\economyland\EconomyLand;
use pocketmine\command\CommandSender;
use pocketmine\math\Vector3;
use pocketmine\Player;

class MoveSubcommand implements Subcommand {
	private $plugin;

	public function __construct(EconomyLand $plugin) {
		$this->plugin = $plugin;
	}

	public function getName(): string {
		return "move";
	}

	public function getUsage(array $args): string {
		return "/land move <part of land ID>";
	}

	public function process(CommandSender $sender, array $args): void {
		if(!$sender instanceof Player) {
			$sender->sendMessage($this->plugin->getMessage('in-game-command'));
			return;
		}

		if(!$sender->hasPermission("economyland.command.land.move")) {
			$sender->sendMessage($this->plugin->getMessage('no-permission'));
			return;
		}

		$id = trim(array_shift($args));
		if($id === '') {
			$sender->sendMessage($this->plugin->getMessage('command-usage', [$this->getUsage($args)]));
			return;
		}

		$lands = $this->plugin->getLandManager()->matchLands($id);
		$count = count($lands);

		if($count > 1) {
			$sender->sendMessage($this->plugin->getMessage('multiple-land-matches', [$id]));
		}elseif($count === 0) {
			$sender->sendMessage($this->plugin->getMessage('no-land-match', [$id]));
		}else{
			$land = $lands[0];

			$option = $land->getOption();
			$allowIn = $option->getAllowIn();

			if($land->isOwner($sender) or $allowIn or $option->isInvitee($sender)) {
				$world = $land->getWorld();
				if($world === null) {
					$sender->sendMessage($this->plugin->getMessage('no-world'));
					return;
				}

				$start = $land->getStart();
				$end = $land->getEnd();

				$position = $world->getSafeSpawn(new Vector3(
					($start->getX() + $end->getX()) / 2,
					$world->getWorldHeight(),
					($start->getY() + $end->getY()) / 2
				));
				$sender->teleport($position);
			}else{
				$sender->sendMessage($this->plugin->getMessage('land-no-permission-move', [$land->getId()]));
			}
		}
	}
}
