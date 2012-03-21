<?php
class Kickfight_Plugin extends Base_Plugin {
	public $enabled = false;
	public $softBans = array();
	public function setup(){
		$this->bot->addCommand("start", "Starts the kickfight", "", USER_LEVEL_MOD);
		$this->bot->addCommand("stop", "Stops the kickfight", "", USER_LEVEL_MOD);
		$this->bot->addCommand("softban", "Softsbans the user", "<user> [<seconds>]", USER_LEVEL_MOD);
	}

	public function softBan($user, $time){
		if($user == $this->bot->nick) return;
		$this->softBans[$user] = time() + $time;
	}

	public function isSoftBanned($user){
		if(array_key_exists($user, $this->softBans)){
			if($this->softBans[$user] > time()){
				return true;
			}
		}
		return false;
	}

	public function onLoop(){
		// Remove softbans
		foreach($this->softBans as $softBan => $banTime){
			if($banTime <= time()){
				unset($this->softBans[$softBan]);
				$this->irc->sendNotice($softBan, "Your softban has expired");
			}
		}
	}

	public function onMode($message, $command, $user, $channel, $hostmask){

	}

	public function onJoin($message, $command, $user, $channel, $hostmask){
		if($this->enabled){
			// No need to op ourselves
			if($user == $this->bot->nick){
				return;
			}

			// Do not allow softban op
			if($this->isSoftBanned($user)){
				$this->irc->sendNotice($user, "You are softbanned for another ".($this->softBans[$user] - time())." seconds");
				return;
			}
			$this->bot->op($channel, $user);
		}
	}
	public function onNick($user, $new, $hostmask){
		// Rename softbans
		if(array_key_exists($user, $this->softBans)){
			$this->softBans[$new] = $this->softBans[$user];
			unset($this->softBans[$user]);
		}
	}

	public function onKick($message, $command, $user, $channel, $hostmask){
		if($this->enabled){
			$this->bot->setTopic($channel, "// Last kicker: $user // Most kicks: // !help");
		}
	}

	public function onCommand($message, $command, $user, $channel, $hostmask){
		$count = 1;
		$argument = explode(" ", trim(str_replace($this->bot->prefix.$command, "", $message, $count)));
		switch($command){
			case "start":
			case "stop":
			case "softban":
				if(!$this->bot->isOwner($user, $hostmask)){;
					$this->irc->sendMessage($channel, "$user: $command can only be called by owners, idiot");
					return;
				}
				break;
		}
		switch($command){
			case "start":
				// We have already started
				if($this->enabled){
					$this->irc->sendMessage($channel, "$user: We have already started, master");
					return;
				}
				$this->enabled = true;
				$this->irc->sendMessage($channel, "Starting KickFight!");
				foreach($this->bot->users as $nick => $host){
					if(!$this->isSoftBanned($nick)){
						$this->bot->op($channel, $nick);
					}
				}	
				break;
			case "stop":
				if(!$this->enabled){
					$this->irc->sendMessage($channel, "$user: We have not started yet, master");
					return;
				}
				$this->enabled = false;
				$this->irc->sendMessage($channel, "Stopping KickFight!");
				foreach ($this->bot->users as $nick => $host) {
					if($nick !== $user){
						$this->bot->deop($channel, $nick);
					}
				}	
				break;
			case "softban":
				if(is_array($argument) && !empty($argument[0])){
					// Do not ban ourselves
					if($argument[0] == $this->bot->nick){
						$this->irc->sendMessage($channel, "$user: You can't ban me, master");
						return;
					}

					// Do not ban yourself
					if($argument[0] == $user){
						$this->irc->sendMessage($channel, "$user: You can't ban yourself, master");
						return;
					}

					// Target user is not here
					if(!array_key_exists($argument[0], $this->bot->users)){
						$this->irc->sendMessage($channel, "$user: $argument[0] is not here, master");
						return;
					}

					// Set up time
					$time = 10;
					if(!empty($argument[1])){
						if(is_numeric($argument[1]) && $argument[1] > 0){
							$time = $argument[1];
						}else{
							$this->irc->sendMessage($channel, "$user: [<seconds>] $argument[1] must be numeric and higher than 0, using default value of 10");
						}
					}
					$this->softban($argument[0], $time);
					$this->bot->deop($channel, $argument[0]);
					$this->irc->sendMessage($channel, "User $argument[0] has been banned for $time seconds by $user");
				}else{
					$this->irc->sendMessage($channel, "$user: ".$this->bot->getCommandUsage("softban", USER_LEVEL_GLOBAL));
					return;
				}
				break;
		}
	}
}
