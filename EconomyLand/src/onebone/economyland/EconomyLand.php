<?php

/*
 * EconomyS, the massive economy plugin with many features for PocketMine-MP
 * Copyright (C) 2013-2020  onebone <me@onebone.me>
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

namespace onebone\economyland;

use onebone\economyapi\EconomyAPI;
use onebone\economyland\form\LandInviteMenuForm;
use onebone\economyland\form\LandOptionForm;
use onebone\economyland\land\Land;
use onebone\economyland\land\LandManager;
use onebone\economyland\land\LandOption;
use onebone\economyland\provider\YamlProvider;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\math\Vector2;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;

final class EconomyLand extends PluginBase {
	public const API_VERSION = 2;

	const FALLBACK_LANGUAGE = 'en';

	private $lang, $fallbackLang;
	/** @var EconomyAPI */
	private $api;
	/** @var PluginConfiguration */
	private $pluginConfig;
	/** @var Vector2[][] */
	private $pos = [];
	/** @var LandManager */
	private $landManager = null;

	public function onEnable() {
		$this->saveDefaultConfig();
		$this->pluginConfig = new PluginConfiguration($this);

		$api = $this->getServer()->getPluginManager()->getPlugin('EconomyAPI');
		if(!$api instanceof EconomyAPI) {
			$this->getLogger()->warning('EconomyAPI is not loaded. EconomyLand will not be enabled because required plugin is not loaded.');
			return;
		}

		$this->api = $api;

		if(EconomyAPI::API_VERSION < 4) {
			$this->getLogger()->warning('Current installed version of EconomyAPI is outdated. Please update EconomyAPI.');
			$this->getLogger()->warning('Expected minimum API version: 4, got ' . EconomyAPI::API_VERSION);
			return;
		}

		$this->loadLanguages();

		if($this->landManager === null) {
			$this->landManager = new LandManager($this, new YamlProvider($this));
		}

		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
	}

	public function getMessage(string $key, array $params = []): string {
		if(isset($this->lang[$key])) {
			return $this->api->replaceParameters($this->lang[$key], $params);
		}elseif(isset($this->fallbackLang[$key])) {
			return $this->api->replaceParameters($this->fallbackLang[$key], $params);
		}

		return $key;
	}

	private function loadLanguages() {
		$lang = strtolower($this->pluginConfig->getLanguage());
		if(!in_array($lang, ['en'])) {
			$lang = self::FALLBACK_LANGUAGE;
		}

		$resource = $this->getResource('lang_' . $lang . '.json');
		if($resource === null) {
			$resource = $this->getResource('lang_en.json');
		}

		$this->lang = json_decode(stream_get_contents($resource), true);
		fclose($resource);

		$resource = $this->getResource('lang_en.json');
		$this->fallbackLang = json_decode(stream_get_contents($resource), true);
		fclose($resource);
	}

	public function onDisable() {
		$this->landManager->close();
	}

	public function getPluginConfiguration(): PluginConfiguration {
		return $this->pluginConfig;
	}

	public function getLandManager(): LandManager {
		return $this->landManager;
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
		switch(array_shift($args)) {
			case 'pos1':
				if(!$sender instanceof Player) {
					$sender->sendMessage($this->getMessage('in-game-command'));
					return true;
				}

				if(!$sender->hasPermission('economyland.command.land.pos')) {
					$sender->sendMessage($this->getMessage('no-permission'));
					return true;
				}

				$vec = $sender->floor();
				$this->pos[$sender->getName()][0] = new Vector2($vec->x, $vec->z);
				$sender->sendMessage($this->getMessage('pos1-set'));
				return true;
			case 'pos2':
				if(!$sender instanceof Player) {
					$sender->sendMessage($this->getMessage('in-game-command'));
					return true;
				}

				if(!$sender->hasPermission('economyland.command.land.pos')) {
					$sender->sendMessage($this->getMessage('no-permission'));
					return true;
				}

				$vec = $sender->floor();
				$this->pos[$sender->getName()][1] = new Vector2($vec->x, $vec->z);
				$sender->sendMessage($this->getMessage('pos2-set'));
				return true;
			case 'buy':
				if(!$sender instanceof Player) {
					$sender->sendMessage($this->getMessage('in-game-command'));
					return true;
				}

				if(!$sender->hasPermission('economyland.command.land.buy')) {
					$sender->sendMessage($this->getMessage('no-permission'));
					return true;
				}

				$username = $sender->getName();
				if(isset($this->pos[$username][0]) and isset($this->pos[$username][1])) {
					$start = $this->pos[$username][0];
					$end = $this->pos[$username][1];

					$land = $this->landManager->createLand($start, $end, $sender->getLevel(), $sender,
						new LandOption([], false, true, false));
					$this->landManager->addLand($land);

					// Renew land list in /land option command usage
					$sender->sendCommandData();

					$size = $land->getEnd()->subtract($land->getStart());

					$sender->sendMessage($this->getMessage('bought-land', [
						$land->getId(), ($size->x + 1) * ($size->y + 1)
					]));
				}else{
					$sender->sendMessage($this->getMessage('set-position'));
				}
				return true;
			case "option":
				if(!$sender instanceof Player) {
					$sender->sendMessage($this->getMessage('in-game-command'));
					return true;
				}

				if(!$sender->hasPermission('economyland.command.land.option')) {
					$sender->sendMessage($this->getMessage('no-permission'));
					return true;
				}

				$id = trim(array_shift($args));
				if($id === '') {
					$sender->sendMessage($this->getMessage('command-usage', ['/land option <part of land ID>']));
					return true;
				}

				$land = $this->getSingleUserLandVerbose($sender, $id);
				if($land === null) return true;

				$sender->sendForm(new LandOptionForm($this, $land));
				return true;
			case 'here':
				if(!$sender instanceof Player) {
					$sender->sendMessage($this->getMessage('in-game-command'));
					return true;
				}

				if(!$sender->hasPermission('economyland.command.land.here')) {
					$sender->sendMessage($this->getMessage('no-permission'));
					return true;
				}

				$vec = $sender->floor();
				$land = $this->landManager->getLandAt($vec->getX(), $vec->getZ(), $sender->getLevel()->getFolderName());
				if($land === null) {
					$sender->sendMessage($this->getMessage('no-land-here'));
					return true;
				}

				$true = $this->getMessage('true');
				$false = $this->getMessage('false');

				$option = $land->getOption();
				$sender->sendMessage($this->getMessage('land-info-line1', [$land->getId(), $land->getOwner()]));
				$sender->sendMessage($this->getMessage('land-info-line2', [implode(', ', array_map(function($val) {
					return $val->getName();
				}, $option->getAllInvitee()))]));
				$sender->sendMessage($this->getMessage('land-info-line3', [
					$option->getAllowIn() ? $true:$false,
					$option->getAllowPickup() ? $true:$false,
					$option->getAllowTouch() ? $true:$false
				]));
				return true;
			case 'invite':
				if(!$sender instanceof Player) {
					$sender->sendMessage($this->getMessage('in-game-command'));
					return true;
				}

				if(!$sender->hasPermission('economyland.command.land.invite')) {
					$sender->sendMessage($this->getMessage('no-permission'));
					return true;
				}

				$id = trim(array_shift($args));
				if($id === '') {
					$sender->sendMessage($this->getMessage('command-usage', ['/land invite <part of land ID>']));
					return true;
				}

				$land = $this->getSingleUserLandVerbose($sender, $id);
				if($land === null) return true;

				$sender->sendForm(new LandInviteMenuForm($this, $land));
				return true;
			default:
				$sender->sendMessage($command->getUsage());
				return true;
		}
	}

	/**
	 * @param Player $player
	 * @param string $id
	 * @return Land[]
	 */
	private function findUserLand(Player $player, string $id): array {
		return array_filter($this->landManager->matchLands($id), function($val) use ($player) {
			return $val->getOwner() === strtolower($player->getName());
		});
	}

	private function getSingleUserLandVerbose(Player $player, string $id): ?Land {
		$lands = $this->findUserLand($player, $id);

		$count = count($lands);
		if($count > 1) {
			$player->sendMessage($this->getMessage('multiple-land-matches', [implode(', ', array_map(function(Land $val) {
				return $val->getId();
			}, $lands))]));
			return null;
		}elseif($count === 0) {
			$player->sendMessage($this->getMessage('no-land-match', [$id]));
			return null;
		}

		// only one matching land here
		return $lands[0];
	}
}
