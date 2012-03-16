<?php
class Base_Plugin {
	public $sock;
	public $irc;
	public function __construct($sock, $irc){
		$this->sock = $sock;
		$this->irc = $irc;
		$this->setup();
	}
	public function setup(){
		if(!in_array("help", $this->irc->commands)){
			$this->irc->commands["help"] = "help";
		}
	}
	public function onLoop(){}
	public function onMode($message, $command, $user, $channel, $hostmask){}
	public function onJoin($message, $command, $user, $channel, $hostmask){}
	public function onPart($message, $command, $user, $channel, $hostmask){}
	public function onCommand($message, $command, $user, $channel, $hostmask){
		$prefix = $this->irc->prefix;
		$msg = "";
		switch($command){
			case $prefix."ping":
				$running = round(microtime(true) - $this->irc->start_time);
				$commit = @exec("git log -n 1 --pretty=format:'%h'");
				$msg = "DataBot version ".VERSION.", commit $commit. since $running s.";
			break;
			case $prefix."help":
				$msg = "Available commands: ";
				$i = 0;

				$commands = array();

				foreach($this->irc->commands as $key => $command){
					// Owner only commands
					if($key[0] == "!" && !$this->irc->isOwner($user, $hostmask)){
						continue;
					}

					$commands[$key] = $command;
				}

				foreach($commands as $key => $command){

					$msg .= $prefix.$command;
					if($key && $i < (count($commands) - 1)){
						$msg .= ", ";
					}
					$i++;
				}
			break;
		}
		if(!empty($msg)){
			$this->irc->sendMessage($channel, $msg);
		}
	}
	public function onMessage(/*$message, $command, $user, $channel, $hostmask*/){}
	public function onTopic(/*$message, $command, $user, $channel, $hostmask*/){}
}
