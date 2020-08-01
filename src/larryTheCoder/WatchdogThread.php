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

use AttachableThreadedLogger;
use pocketmine\snooze\SleeperNotifier;
use pocketmine\Thread;
use pocketmine\utils\Process;

class WatchdogThread extends Thread {

	private $running = true;

	/** @var int */
	private $timeout; // The value that is responsible for timeout.
	/** @var SleeperNotifier */
	private $notifier;
	/** @var boolean */
	public $isResponded = true;

	/** @var int */
	private $preTimeout = 0;
	/** @var AttachableThreadedLogger */
	private $logger;

	public function __construct(SleeperNotifier $handler, int $timeout, AttachableThreadedLogger $logger){
		$this->notifier = $handler;
		$this->timeout = $timeout;
		$this->logger = $logger;
	}

	public function run(){
		$this->registerClassLoader();

		$unit = 1000000;
		while($this->running){
			if(!$this->isResponded){
				$this->performTimeout();
				$this->notifier->wakeupSleeper();

				$this->synchronized(function() use ($unit){
					$this->wait($unit); // 1 seconds
				});
			}else{
				$this->preTimeout = 0;

				// 1000000 = 1 seconds
				$this->synchronized(function() use ($unit){
					$this->wait(15 * $unit); // Seconds to milliseconds.

					$this->notifier->wakeupSleeper();
					$this->isResponded = false;
				});
			}
		}
	}

	/**
	 * Responsible to kill the server if the server doesn't response
	 * more than the requested amount of time.
	 */
	private function performTimeout(): void{
		$this->preTimeout++;
		if($this->preTimeout > $this->timeout){
			$this->logger->emergency("KILLING SERVER, SERVER IS NOW IN AN UNRECOVERABLE STATE.");

			@Process::kill(getmypid());
		}
	}

	public function quit(){
		$this->running = false;

		parent::quit();
	}

	public function getThreadName(): string{
		return "Watchdog";
	}
}