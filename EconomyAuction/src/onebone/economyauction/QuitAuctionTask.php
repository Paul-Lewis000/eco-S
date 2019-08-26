<?php

/*
 * EconomyS, the massive economy plugin with many features for PocketMine-MP
 * Copyright (C) 2013-2017  onebone <jyc00410@gmail.com>
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
 
 namespace onebone\economyauction;
 
 use pocketmine\scheduler\Task;
 
 class QuitAuctionTask extends Task{
     protected $plugin;
	 private $player;
	 
	 public function __construct(EconomyAuction $plugin, $player){
	     $this->plugin = $plugin;
		 
		 $this->player = strtolower($player);
	 }
	 
	 public function onRun(int $currentTick){
		 $this->plugin->quitAuction($this->player);
	 }
 }
