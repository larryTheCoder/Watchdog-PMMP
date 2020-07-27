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

use pocketmine\snooze\SleeperNotifier;
use pocketmine\Thread;
use pocketmine\utils\MainLogger;
use pocketmine\utils\Utils;

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

	public function __construct(SleeperNotifier $handler, int $timeout){
		$this->notifier = $handler;
		$this->timeout = $timeout;
	}

	public function run(){
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
				$this->isResponded = false;
				$this->notifier->wakeupSleeper();

				// 1000000 = 1 seconds
				$this->synchronized(function() use ($unit){
					$this->wait(15 * $unit); // Seconds to milliseconds.
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
			MainLogger::$logger->emergency("--------- SERVER STOPPED RESPONDING ---------");
			@self::killSc(getmypid());
		}
	}

	public static function killSc($pid): void{
		if(MainLogger::isRegisteredStatic()){
			MainLogger::getLogger()->syncFlushBuffer();
		}
		switch(Utils::getOS()){
			case Utils::OS_WINDOWS:
				exec("taskkill.exe /F /PID $pid > NUL");
				break;
			case Utils::OS_MACOS:
			case Utils::OS_LINUX:
			default:
				if(function_exists("posix_kill")){
					posix_kill($pid, 9); //SIGKILL
				}else{
					exec("kill -9 $pid > /dev/null 2>&1");
				}
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