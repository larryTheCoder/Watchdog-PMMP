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

use pocketmine\Thread;
use pocketmine\utils\MainLogger;
use pocketmine\utils\Utils;

class WatchdogThread extends Thread {

	private $running = true;

	/** @var int */
	private $nowTick = 0;

	/** @var int */
	private $timeoutConf;

	public function __construct(int $timeout){
		$this->timeoutConf = $timeout;
		$this->timeout = $timeout;
	}

	public function run(){
		MainLogger::getLogger()->debug("Started watchdog thread");

		$lastTick = 0;
		while($this->running){
			switch(true):
				/** @noinspection PhpMissingBreakStatementInspection */
				case ($lastTick > $this->nowTick):  // The server somehow time travelled back to the past.
					MainLogger::getLogger()->debug("Server has travelled back to the past?");
				case ($lastTick < $this->nowTick): // The server is ticking correctly.
					$this->performResponse();

					$lastTick = $this->nowTick;
					break;
				case ($lastTick === $this->nowTick): // The server didn't response in the specified time.
					$this->performTimeout();
					break;

			endswitch;

			sleep(1);
		}

		MainLogger::getLogger()->debug("Stopping watchdog thread");
	}

	/** @var int */
	private $timeout; // The value that is responsible for timeout.

	private function performResponse(): void{
		$this->timeout = $this->timeoutConf;
	}

	/**
	 * Responsible for killing the server if the server doesn't response
	 * more than the requested amount of time.
	 */
	private function performTimeout(): void{
		$this->timeout--;
		if($this->timeout <= 0){
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

	public function tickWatchdog(int $serverTick){
		$this->nowTick = $serverTick;
	}

	public function getThreadName(): string{
		return "Watchdog";
	}
}