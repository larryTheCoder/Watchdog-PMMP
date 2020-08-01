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

namespace larryTheCoder;

use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\snooze\SleeperNotifier;

class Watchdog extends PluginBase {

	/**
	 * The first execution of Watchdog PocketMine startup.
	 * <p>
	 * Way of how this works is that, plugin will create a WatchdogThread instance
	 * in the server class, the instance however are initialized anonymously. Which
	 * also provides "/reload" support, to avoid multiple instances of WatchdogThread
	 * being created.
	 * <p>
	 * In other hand, a notify task that is running every 5ms will eventually notifies
	 * Watchdog thread, and if the condition where the server has stopped responding,
	 * the thread will attempts to tick its timeout, which is set in the config file.
	 * <p>
	 * And if the timeout reached, the server will be killed using a script.
	 */
	public function onEnable(){
		$this->saveResource("config.yml");

		// Watchdog has already been initialized.
		if(isset($this->getServer()->watchdog)) return;

		$notifier = new SleeperNotifier();
		Server::getInstance()->getTickSleeper()->addNotifier($notifier, function(): void{
			$this->handleNotifications();
		});

		$this->getServer()->watchdog = new WatchdogThread($notifier, $this->getConfig()->get("timeout", 60), Server::getInstance()->getLogger());
		$this->getServer()->watchdog->start(PTHREADS_INHERIT_NONE);
	}

	private function handleNotifications(){
		/** @var WatchdogThread $wd */
		/** @noinspection PhpUndefinedFieldInspection */
		$wd = Server::getInstance()->watchdog;

		$wd->isResponded = true;
	}
}