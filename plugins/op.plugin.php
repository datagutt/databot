<?php
class OP_Plugin extends Base_Plugin {
	public $autoOP = true;
	public function setup(){
		$this->bot->addCommand("op", "Gives OP to the user", "[<user>] [<channel>]", USER_LEVEL_MOD);
		$this->bot->addCommand("deop", "Removes OP from the user", "[<user>] [<channel>]",  USER_LEVEL_MOD);
		$this->bot->addCommand("voice", "Gives voice to the user", "[<user>] [<channel>]", USER_LEVEL_MOD);
		$this->bot->addCommand("devoice", "Remove voice from the user", "[<user>] [<channel>]", USER_LEVEL_MOD);
		$this->bot->addCommand("mute", "Gives voice to the user", "[<user>] [<channel>]", USER_LEVEL_MOD);
		$this->bot->addCommand("unmute", "Remove voice from the user", "[<user>] [<channel>]", USER_LEVEL_MOD);
		$this->bot->addCommand("kick", "Kicks the user", "[<user>] [<channel>]", USER_LEVEL_MOD);
		$this->bot->addCommand("kickban", "Kicks and bans the user", "[<user>] [<channel]", USER_LEVEL_MOD);
		$this->bot->addCommand("topic", "Sets the topic", "<topic>", USER_LEVEL_MOD);
		$this->bot->addCommand("invite", "Invites a user", "<user> [<channel>]", USER_LEVEL_MOD);
		$this->bot->addCommand("say", "Makes the bot say something", "<channel> <message>", USER_LEVEL_ADMIN);
		$this->bot->addCommand("nick", "Changes the nick of the bot", "<nick>", USER_LEVEL_ADMIN);
		$this->bot->addCommand("join", "Join the specified channel", "<channel>", USER_LEVEL_ADMIN);
		$this->bot->addCommand("part", "Part the specified channel", "<channel>", USER_LEVEL_ADMIN);
	}
	public function onJoin($message, $command, $user, $channel, $hostmask){
		if($this->autoOP){
			$userLevel = $this->bot->getUserLevel($user, $hostmask);
			if($userLevel >= USER_LEVEL_MOD){
				$this->irc->op($channel, $user);
			}
		}
	}
	public function onCommand($message, $command, $user, $channel, $hostmask){
		$argument = explode(" ", trim(substr($message, strlen($this->bot->prefix.$command))));
		$userLevel = $this->bot->getUserLevel($user, $hostmask);

		if(!$this->bot->isCommand($command, $userLevel)){
			return;
		}
		switch($command){
			case "op":
				if(is_array($argument) && !empty($argument[0])){
					if(!empty($argument[1])){
						$this->irc->op($argument[1], $argument[0]);
					}else{
						$this->irc->op($channel, $argument[0]);
					}
				}else{
					$this->irc->op($channel, $user);
				}
			break;
			case "deop":
				if(is_array($argument) && !empty($argument[0])){
					if(!empty($argument[1])){
						$this->irc->deop($argument[1], $argument[0]);
					}else{
						$this->irc->deop($channel, $argument[0]);
					}
				}else{
					$this->irc->deop($channel, $user);
				}
			break;
			case "voice":
				if(is_array($argument) && !empty($argument[0])){
					if(!empty($argument[1])){
						$this->irc->voice($argument[1], $argument[0]);
					}else{
						$this->irc->voice($channel, $argument[0]);
					}
				}else{
					$this->irc->voice($channel, $user);
				}
			break;
			case "devoice":
				if(is_array($argument) && !empty($argument[0])){
					if(!empty($argument[1])){
						$this->irc->devoice($argument[1], $argument[0]);
					}else{
						$this->irc->devoice($channel, $argument[0]);
					}
				}else{
					$this->irc->devoice($channel, $user);
				}
			break;
			case "mute":
				if(is_array($argument) && !empty($argument[0])){
					if(!empty($argument[1])){
						$this->irc->mute($argument[1], $argument[0]);
					}else{
						$this->irc->mute($channel, $argument[0]);
					}
				}else{
					$this->irc->mute($channel, $user);
				}
			break;
			case "unmute":
				if(is_array($argument) && !empty($argument[0])){
					if(!empty($argument[1])){
						$this->irc->unmute($argument[1], $argument[0]);
					}else{
						$this->irc->unmute($channel, $argument[0]);
					}
				}else{
					$this->irc->unmute($channel, $user);
				}
			break;
			case "kick":
				if(is_array($argument) && !empty($argument[0])){
					if(!empty($argument[1])){
						$this->irc->kick($argument[1], $argument[0]);
					}else{
						$this->irc->kick($channel, $argument[0]);
					}
				}else{
					$this->irc->kick($channel, $user);
				}
			break;
			case "kickban":
				if(is_array($argument) && !empty($argument[0])){
					if(!empty($argument[1])){
						$this->irc->ban($argument[1], $argument[0]);
						$this->irc->kick($argument[1], $argument[0]);
					}else{
						$this->irc->ban($channel, $argument[0]);
						$this->irc->kick($channel, $argument[0]);
					}
				}else{
					$this->irc->ban($channel, $user);
					$this->irc->kick($channel, $user);
				}
			break;
			case "topic":
				if(is_array($argument) && !empty($argument[0])){
					$topic = substr($message, strlen($this->bot->prefix.$command." "));
					$this->irc->setTopic($channel, $topic);
				}else{
					$this->irc->sendMessage($channel, $this->bot->prefix.$command." ".$this->bot->getCommandUsage($command, $userLevel));
				}

			break;
			case "invite":
                               if(is_array($argument) && !empty($argument[0])){
					if(!empty($argument[1])){
						$this->irc->invite($argument[1], $argument[0]);
					}else{
						$this->irc->invite($channel, $argument[0]);
					}
				}else{
					$this->irc->sendMessage($channel, $this->bot->prefix.$command." ".$this->bot->getCommandUsage($command, $userLevel));
				}
			break;
			case "say":
				if(is_array($argument)){
					$channel = $argument[0];
					$msg = substr($message, strlen($this->bot->prefix.$command." ".$channel." "));
					$this->irc->sendMessage($channel, $msg);
				}else{
					$this->irc->sendMessage($channel, $this->bot->prefix.$command." ".$this->bot->getCommandUsage($command, $userlevel));
				}
			break;
			case "nick":
				if(is_array($argument) && !empty($argument[0])){
					$this->irc->nick($argument[0]);
				}else{
					$this->irc->sendMessage($channel, $this->bot->prefix.$command." ".$this->bot->getCommandUsage($command, $userlevel));
				}
			break;
			case "join":
				if(is_array($argument) && !empty($argument[0])){
					$this->irc->join($argument[0]);
				}else{
					$this->irc->sendMessage($channel, $this->bot->prefix.$command." ".$this->bot->getCommandUsage($command, $userlevel));
				}
			break;
			case "part":
				if(is_array($argument) && !empty($argument[0])){
					$this->irc->part($argument[0]);
				}else{
					$this->irc->sendMessage($channel, $this->bot->prefix.$command." ".$this->bot->getCommandUsage($command, $userlevel));
				}
			break;
		}
	}
}
