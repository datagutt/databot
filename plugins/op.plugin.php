<?php
class OP_Plugin extends Base_Plugin {
	public $autoOP = true;
	public function setup(){
		$this->bot->addCommand("op", "Gives OP to the user", "[<user>]", USER_LEVEL_MOD);
		$this->bot->addCommand("deop", "Removes OP from the user", "[<user>]",  USER_LEVEL_MOD);
		$this->bot->addCommand("voice", "Gives voice to the user", "[<user>]", USER_LEVEL_MOD);
		$this->bot->addCommand("devoice", "Remove voice from the user", "[<user>]", USER_LEVEL_MOD);
		$this->bot->addCommand("mute", "Gives voice to the user", "[<user>]", USER_LEVEL_MOD);
		$this->bot->addCommand("unmute", "Remove voice from the user", "[<user>]", USER_LEVEL_MOD);
		$this->bot->addCommand("kick", "Kicks the user", "[<user>]", USER_LEVEL_MOD);
		$this->bot->addCommand("kickban", "Kicks and bans the user", "[<user>]", USER_LEVEL_OWNER);
		$this->bot->addCommand("topic", "Sets the topic", "<topic>", USER_LEVEL_MOD);
		$this->bot->addCommand("say", "Makes the bot say something", "<message>", USER_LEVEL_OWNER);
		$this->bot->addCommand("nick", "Changes the nick of the bot", "<nick>", USER_LEVEL_OWNER);
		$this->bot->addCommand("join", "Join the specified channel", "<channel>", USER_LEVEL_OWNER);
		$this->bot->addCommand("part", "Part the specified channel", "<channel>", USER_LEVEL_OWNER);
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
		$count = 1;
		$argument = explode(" ", trim(str_replace($this->bot->prefix.$command, "", $message, $count)));
		$userLevel = $this->bot->getUserLevel($user, $hostmask);

		if(!$this->bot->isCommand($command, $userLevel)){
			return;
		}
		switch($command){
			case "op":
				if(is_array($argument) && !empty($argument[0])){
					$this->irc->op($channel, $argument[0]);
				}else{
					$this->irc->op($channel, $user);
				}
			break;
			case "deop":
				if(is_array($argument) && !empty($argument[0])){
					$this->irc->deop($channel, $argument[0]);
				}else{
					$this->irc->deop($channel, $user);
				}
			break;
			case "voice":
				if(is_array($argument) && !empty($argument[0])){
					$this->irc->voice($channel, $argument[0]);
				}else{
					$this->irc->voice($channel, $user);
				}
			break;
			case "devoice":
				if(is_array($argument) && !empty($argument[0])){
					$this->irc->devoice($channel, $argument[0]);
				}else{
					$this->irc->devoice($channel, $user);
				}
			case "mute":
				if(is_array($argument) && !empty($argument[0])){
					$this->irc->mute($channel, $argument[0]);
				}else{
					$this->irc->mute($channel, $user);
				}
			break;
			case "unmute":
				if(is_array($argument) && !empty($argument[0])){
					$this->irc->unmute($channel, $argument[0]);
				}else{
					$this->irc->unmute($channel, $user);
				}
			break;
			case "kick":
				if(is_array($argument) && !empty($argument[0])){
					$this->irc->kick($channel, $argument[0]);
				}else{
					$this->irc->kick($channel, $user);
				}
			break;
			case "kickban":
				if(is_array($argument) && !empty($argument[0])){
					$this->irc->ban($channel, $argument[0]);
					$this->irc->kick($channel, $argument[0]);
				}else{
					$this->irc->sendMessage($channel, $this->bot->getCommandUsage($command, USER_LEVEL_OWNER));
				}
			break;
			case "topic":
				if(is_array($argument) && !empty($argument[0])){
					$topic = "";
					foreach($argument as $line){
						$topic .= $line;
						$topic .= " ";
					}
					$this->irc->setTopic($channel, $topic);
				}
			break;
			case "say":
				if(is_array($argument)){
					$channel = $argument[0];
					unset($argument[0]);
					$msg = "";
					foreach($argument as $line){
						$msg .= $line;
						$msg .= " ";
					}
					$this->irc->sendMessage($channel, $msg);
				}else{
					$this->irc->sendMessage($channel, $this->bot->prefix.$command." ".$this->bot->getCommandUsage($command, USER_LEVEL_OWNER));
				}
			break;
			case "nick":
				if(is_array($argument) && !empty($argument[0])){
					$this->irc->nick($argument[0]);
				}else{
					$this->irc->sendMessage($channel, $this->bot->prefix.$command." ".$this->bot->getCommandUsage($command, USER_LEVEL_OWNER));
				}
			break;
			case "join":
				if(is_array($argument) && !empty($argument[0])){
					$this->irc->join($argument[0]);
				}else{
					$this->irc->sendMessage($channel, $this->bot->prefix.$command." ".$this->bot->getCommandUsage($command, USER_LEVEL_OWNER));
				}
			break;
			case "part":
				if(is_array($argument) && !empty($argument[0])){
					$this->irc->part($argument[0]);
				}else{
					$this->irc->sendMessage($channel, $this->bot->prefix.$command." ".$this->bot->getCommandUsage($command, USER_LEVEL_OWNER));
				}
			break;
		}
	}
}
