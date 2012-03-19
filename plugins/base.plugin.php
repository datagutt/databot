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
		$this->irc->addCommand("level", "Shows a users bot control level", "[<user>]", USER_LEVEL_GLOBAL);
		if(!$this->irc->isCommand("help")){
			$this->irc->addCommand("help", "Shows commands and how to use them", "[<command>]", USER_LEVEL_GLOBAL);
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
		$count = 1;
		$argument = explode(" ", trim(str_replace($command, "", $message, $count)));
		$userLevel = $this->irc->getUserLevel($user, $hostmask);
		switch($command){
			case $prefix."level":
				$this->irc->sendMessage($channel, $user.": Your bot control level is: $userLevel");
				break;
			case $prefix."ping":
				$running = round(microtime(true) - $this->irc->start_time);
				$commit = @exec("git log -n 1 --pretty=format:'%h'");
				$this->irc->sendMessage($channel, "$user: ".BOT." version ".VERSION."; commit $commit; uptime ".$running."s.");
			break;
			case $prefix."help":
				if(is_array($argument) && !empty($argument[0])){
					if(!$this->irc->isCommand($argument[0], $userLevel)){
						$this->irc->sendMessage($channel, $user.": Command $argument[0] does not exist or you are not authorized to perform it");
						return;
					}
					$usage = $this->irc->getCommandUsage($argument[0], $userLevel);
					$description = $this->irc->getCommandDescription($argument[0], $userLevel);
					$this->irc->sendMessage($channel, "$user: $prefix$argument[0] $usage");
					$this->irc->sendMessage($channel, "$user: $description");
				}else{
					$msg = "Available commands: ";

					$userLevel = $this->irc->getUserLevel($user, $hostmask);

					foreach($this->irc->commands as $command => $levels){
						if($this->irc->isCommand($command, $userLevel)){
							$msg .= $prefix.$command;
							$msg .= " ";
						}
					}
					$this->irc->sendMessage($channel, "$user: ".$msg);
				}
			break;
		}
	}
	public function onMessage($message, $command, $user, $channel, $hostmask){}
	public function onTopic($message, $command, $user, $channel, $hostmask){}
}
