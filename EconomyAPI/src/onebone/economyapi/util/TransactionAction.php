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

namespace onebone\economyapi\util;

use onebone\economyapi\currency\Currency;
use pocketmine\Player;

class TransactionAction {
	/** @var int */
	private $type;
	/** @var string */
	private $player;
	/** @var float */
	private $amount;
	/** @var Currency */
	private $currency;

	/**
	 * TransactionAction constructor.
	 * @param int $type
	 * @param string|Player $player
	 * @param float $amount
	 */
	public function __construct(int $type, $player, float $amount, Currency $currency) {
		if($type > 2) {
			throw new \InvalidArgumentException("Invalid transaction type given: $type");
		}

		if($player instanceof Player) {
			$player = $player->getName();
		}

		$this->type = $type;
		$this->player = $player;
		$this->amount = $amount;
		$this->currency = $currency;
	}

	public function getType(): int {
		return $this->type;
	}

	public function getPlayer(): string {
		return $this->player;
	}

	public function getAmount(): float {
		return $this->amount;
	}

	public function getCurrency(): Currency {
		return $this->currency;
	}
}
