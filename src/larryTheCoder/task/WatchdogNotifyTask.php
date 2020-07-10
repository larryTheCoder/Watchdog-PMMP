<?php
/**
 * This file is part of Watchdog-PMMP.
 *
 * Watchdog-PMMP is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Watchdog-PMMP is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Watchdog-PMMP.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types = 1);

namespace larryTheCoder\task;

use larryTheCoder\WatchdogThread;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class WatchdogNotifyTask extends Task {

	public function onRun(int $currentTick){
		if(!isset(Server::getInstance()->watchdog)){
			return;
		}

		/** @var WatchdogThread $watchdog */
		$watchdog = Server::getInstance()->watchdog;
		$watchdog->tickWatchdog($currentTick);
	}
}