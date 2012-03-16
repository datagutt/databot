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
		if(!$this->irc->isCommand("help")){
			$this->irc->addCommand("help", "Shows commands and how to use them", "[command]", COMMAND_LEVEL_GLOBAL);
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
				$msg = "".BOT." version ".VERSION.", commit $commit. since $running s.";
			break;
			case $prefix."help":
				$msg = "Available commands: ";
				$i = 0;
				// this makes sure owner commands dont get shown. This needs a rewrite but it works for now
				$commands = $this->irc->commands;

				foreach($commands as $key => $command){
					// Owner only commands
					$level = $this->irc->isOwner($user, $hostmask) ? COMMAND_LEVEL_OWNER : COMMAND_LEVEL_GLOBAL;
					if($this->irc->isCommand($key, $level)){
						unset($commands[$key]);
					}
				}

				foreach($commands as $key => $command){
					$msg .= $prefix.$key;
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
