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
			$this->irc->addCommand("help", "Shows commands and how to use them", "[command]", USER_LEVEL_GLOBAL);
		}
	}
	public function onLoop(){}
	public function onNick($user, $new, $hostmask){}
	public function onMode($message, $command, $user, $channel, $hostmask){}
	public function onJoin($message, $command, $user, $channel, $hostmask){}
	public function onPart($message, $command, $user, $channel, $hostmask){}
	public function onKick($message, $command, $user, $channel, $hostmask){}
	public function onCommand($message, $command, $user, $channel, $hostmask){
		$prefix = $this->irc->prefix;
		$msg = "";
		switch($command){
			case $prefix."ping":
				$running = round(microtime(true) - $this->irc->start_time);
				$commit = @exec("git log -n 1 --pretty=format:'%h'");
				$msg = BOT." version ".VERSION."; commit $commit; uptime ".$running."s.";
			break;
			case $prefix."help":
				$msg = "Available commands: ";

				$userLevel = $this->irc->getUserLevel($user, $hostmask);

				foreach($this->irc->commands as $command => $levels){
					if($this->irc->isCommand($command, $userLevel)){
						$msg .= $prefix.$command;
						$msg .= " ";
					}
				}
			break;
		}
		if(!empty($msg)){
			$this->irc->sendMessage($channel, $user.": ".$msg);
		}
	}
	public function onMessage(/*$message, $command, $user, $channel, $hostmask*/){}
	public function onTopic(/*$message, $command, $user, $channel, $hostmask*/){}
}
