<?php
class Base_Plugin {
	public $sock;
	public $bot;
	public function __construct($sock, $bot, $irc){
		$this->sock = $sock;
		$this->bot = $bot;
		$this->irc = $irc;
		$this->setup();
	}
	public function setup(){
		if(!$this->bot->isCommand("help")){
			$this->bot->addCommand("help", "Shows commands and how to use them", "[<command>]", USER_LEVEL_GLOBAL);
		}
		$this->bot->addCommand("userlevel", "Shows a users bot control level", "[<user>]", USER_LEVEL_GLOBAL);
		$this->bot->addCommand("set", "Set a property", "<property> <value>", USER_LEVEL_OWNER);
		$this->bot->addCommand("owners", "List owners", "", USER_LEVEL_GLOBAL);
		$this->bot->addCommand("moderators", "List moderators", "", USER_LEVEL_GLOBAL);
	}
	public function onLoop(){}
	public function onNick($user, $new, $hostmask){}
	public function onMode($message, $command, $user, $channel, $hostmask){}
	public function onJoin($message, $command, $user, $channel, $hostmask){}
	public function onPart($message, $command, $user, $channel, $hostmask){}
	public function onKick($message, $command, $user, $channel, $hostmask){}
	public function onCommand($message, $command, $user, $channel, $hostmask){
		$count = 1;
		$argument = explode(" ", trim(str_replace($this->bot->prefix.$command, "", $message, $count)));
		$userLevel = $this->bot->getUserLevel($user, $hostmask);

		if(!$this->bot->isCommand($command, USER_LEVEL_OWNER)){
			$this->irc->sendMessage($channel, "$user: Command '$command' does not exist");
			return; 
		}

		if($this->bot->getCommandMinimumLevel($command) > $userLevel){
			$this->irc->sendMessage($channel, $user.": You are not authorized to perform '$command'");
			return;
		}

		switch($command){
			case "userlevel":
				if(is_array($argument) && !empty($argument[0])){
					// Target user is not here
					if(!array_key_exists($argument[0], $this->bot->users)){
						$this->irc->sendMessage($channel, "$user: Unknown user $argument[0]");
						return;
					}
					$userLevel = $this->bot->getUserLevel($argument[0], $this->bot->users[$argument[0]]);
					$this->irc->sendMessage($channel, $user.": $argument[0]'s bot control level is: $userLevel");
				}else{
					$this->irc->sendMessage($channel, $user.": Your bot control level is: $userLevel");
				}
			break;
			case "set":
				switch($argument[0]){
					case "autoOP":
						if(isset($argument[1])){
							$arg1 = trim($argument[1]);
							if($arg1 == "true" || $arg1 == "1"){
								$this->bot->setPluginProperty("OP_Plugin", "autoOP", true);
							}else{
								$this->bot->setPluginProperty("OP_Plugin", "autoOP", false);			}
						}
					break;
				}
				$passedVars = array(
					"message" => $message,
					"command" => $command,
					"user" => $user,
					"channel" => $channel,
					"hostmask" => $hostmask
				);
				$this->bot->triggerEvent("onSet", $passedVars);
			break;
			case "owners":
				if(is_array($argument) && !empty($argument[0])){
					if($userLevel < USER_LEVEL_OWNER){
						$this->irc->sendMessage($channel, $user.": You are not authorized to add/remove owners");
						return;
					}
					if(empty($argument[1])){
						$this->irc->sendMessage($channel, "$user: Please specify a user");
						return;
					}
					$owners = explode(",", $argument[1]);
					switch($argument[0]){
						case "add":
							foreach($owners as $owner){
								$owner = trim($owner);
								$this->bot->owners[$owner] = $this->bot->users[$owner];
								$this->irc->sendMessage($channel, "$user: $owner!".$this->bot->users[$owner]." added to owners list");
							}
							break;
							case "remove":
								foreach($owners as $owner){
									$owner = trim($owner);
									unset($this->bot->owners[$owner]);
									$this->irc->sendMessage($channel, "$user: $owner!".$this->bot->users[$owner]." removed from owners list");
								}
							break;
							default:
								$this->irc->sendMessage($channel, "$user: Unknown argument '$argument[1]'");
							break;
					}
				}else{
					$msg = "Owners: ";
					foreach($this->bot->owners as $owner => $hostmask){
						$msg .= $owner;
						$msg .= " ";
					}
					$this->irc->sendMessage($channel, "$user: $msg");
				}
			break;
			case "moderators":
				if(is_array($argument) && !empty($argument[0])){
					if($userLevel < USER_LEVEL_OWNER){
						$this->irc->sendMessage($channel, $user.": You are not authorized to add/remove moderators");
						return;
					}
					if(empty($argument[1])){
						$this->irc->sendMessage($channel, "$user: Please specify a user");
						return;
					}
					$mods = explode(",", $argument[1]);
					switch($argument[0]){
						case "add":
							foreach($mods as $mod){
								$mod = trim($mod);
								$this->bot->moderators[$mod] = $this->bot->users[$mod];
								$this->irc->sendMessage($channel, "$user: $mod!".$this->bot->users[$mod]." added to moderators list");
							}
							break;
							case "remove":
							foreach($mods as $mod){
								$mod = trim($mod);
									unset($this->bot->owners[$mod]);
									$this->irc->sendMessage($channel, "$user: $mod!".$this->bot->users[$mod]." removed from moderators list");
								}
							break;
							default:
								$this->irc->sendMessage($channel, "$user: Unknown argument '$argument[1]'");
							break;
					}
				}else{
					$msg = "Moderators: ";
					foreach($this->bot->moderators as $moderator => $hostmask){
						$msg .= $moderator;
						$msg .= " ";
					}
					$this->irc->sendMessage($channel, "$user: $msg");
				}
			break;
			case "ping":
				$running = round(microtime(true) - $this->bot->start_time);
				$commit = @exec("git log -n 1 --pretty=format:'%h'");
				$this->irc->sendMessage($channel, "$user: ".BOT." version ".VERSION."; commit $commit; uptime ".$running."s.");
			break;
			case "help":
				if(is_array($argument) && !empty($argument[0])){
					if(!$this->bot->isCommand($argument[0], USER_LEVEL_OWNER)){
						$this->irc->sendMessage($channel, "$user: Command '$argument[0]' does not exist");
						return; 
					}
					if($this->bot->getCommandMinimumLevel($argument[0]) > $userLevel){
						$this->irc->sendMessage($channel, $user.": You are not authorized to perform '$argument[0]'");
						return;
					}
					$usage = $this->bot->getCommandUsage($argument[0], $userLevel);
					$description = $this->bot->getCommandDescription($argument[0], $userLevel);
					$this->irc->sendMessage($channel, "$user: ".$this->bot->prefix."$argument[0] $usage");
					$this->irc->sendMessage($channel, "$user: $description");
				}else{
					$msg = "Available commands: ";

					$userLevel = $this->bot->getUserLevel($user, $hostmask);

					foreach($this->bot->commands as $command => $levels){
						if($this->bot->isCommand($command, $userLevel)){
							$msg .= $this->bot->prefix.$command;
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
