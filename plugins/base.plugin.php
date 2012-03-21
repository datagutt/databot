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
			$this->irc->addCommand("help", "Shows commands and how to use them", "[<command>]", USER_LEVEL_GLOBAL);
		}
		$this->irc->addCommand("userlevel", "Shows a users bot control level", "[<user>]", USER_LEVEL_GLOBAL);
		$this->irc->addCommand("set", "Set a property", "<property> <value>", USER_LEVEL_OWNER);
		$this->irc->addCommand("owners", "List owners", "", USER_LEVEL_GLOBAL);
		$this->irc->addCommand("moderators", "List moderators", "", USER_LEVEL_GLOBAL);
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
			case $prefix."userlevel":
				if(is_array($argument) && !empty($argument[0])){
					// Target user is not here
					if(!array_key_exists($argument[0], $this->irc->users)){
						$this->irc->sendMessage($channel, "$user: Unknown user $argument[0]");
						return;
					}
					$userLevel = $this->irc->getUserLevel($argument[0], $this->irc->users[$argument[0]]);
					$this->irc->sendMessage($channel, $user.": $argument[0]'s bot control level is: $userLevel");
				}else{
					$this->irc->sendMessage($channel, $user.": Your bot control level is: $userLevel");
				}
			break;
			case $prefix."set":
				switch($argument[0]){
					case "autoOP":
						if(isset($argument[1])){
							$this->irc->autoOP = (boolean) $argument[1];
						}
					break;
					case "owners":
						if(isset($argument[1]) && isset($argument[1])){
							$owners = explode(",", $argument[1]);
							foreach($owners as $owner){
								$owner = trim($owner);
								$this->irc->owners[$owner] = $this->irc->users[$owner];
								$this->irc->sendMessage($channel, "$user: $owner!".$this->irc->users[$owner]." added to owners list");
							}
						}
					break;
					case "mods":
						if(isset($argument[1]) && isset($argument[1])){
							$mods = explode(",", $argument[1]);
							foreach($mods as $mod){
								$mod = trim($mod);
								$this->irc->moderators[$mod] = $this->irc->users[$mod];
								$this->irc->sendMessage($channel, "$user: $mod!".$this->irc->users[$mod]." added to moderators list");
							}
						}
					break;
				}
			break;
			case $prefix."owners":
				$msg = "Owners: ";
				foreach($this->irc->owners as $owner => $hostmask){
					$msg .= $owner;
					$msg .= " ";
				}
				$this->irc->sendMessage($channel, "$user: $msg");
			break;
			case $prefix."moderators":
				$msg = "Moderators: ";
				foreach($this->irc->moderators as $moderator => $hostmask){
					$msg .= $moderator;
					$msg .= " ";
				}
				$this->irc->sendMessage($channel, "$user: $msg");
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
