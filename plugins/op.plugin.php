<?php
class OP_Plugin extends Base_Plugin {
	public function setup(){
		$this->irc->addCommand("op", "Gives OP to the user", "[<user>]", USER_LEVEL_OWNER);
		$this->irc->addCommand("deop", "Removes OP from the user", "[<user>]",  USER_LEVEL_OWNER);
		$this->irc->addCommand("voice", "Gives voice to the user", "[<user>]", USER_LEVEL_OWNER);
		$this->irc->addCommand("devoice", "Remove voice from the user", "[<user>]", USER_LEVEL_OWNER);
		$this->irc->addCommand("kick", "Kicks the user", "[<user>]", USER_LEVEL_OWNER);
		$this->irc->addCommand("kickban", "Kicks and bans the user", "[<user>]", USER_LEVEL_OWNER);
		$this->irc->addCommand("topic", "Sets the topic", "<topic>", USER_LEVEL_OWNER);
		$this->irc->addCommand("say", "Makes the bot say something", "<message>", USER_LEVEL_OWNER);
		$this->irc->addCommand("owners", "List owners", "", USER_LEVEL_GLOBAL);
		$this->irc->addCommand("moderators", "List moderators", "", USER_LEVEL_GLOBAL);
		$this->irc->addCommand("join", "Join the specified channel", "<channel>", USER_LEVEL_OWNER);
		$this->irc->addCommand("part", "Part the specified channel", "<channel>", USER_LEVEL_OWNER);
	}
	public function onCommand($message, $command, $user, $channel, $hostmask){
		$prefix = $this->irc->prefix;
		$count = 1;
		$argument = explode(" ", trim(str_replace($command, "", $message, $count)));
		$userLevel = $this->irc->getUserLevel($user, $hostmask);

		if(!$this->irc->isCommand(substr($command, 1), $userLevel)){
			return;
		}
		switch($command){
			case $prefix."op":
				if(is_array($argument) && !empty($argument[0])){
					$this->irc->op($channel, $argument[0]);
				}else{
					$this->irc->op($channel, $user);
				}
			break;
			case $prefix."deop":
				if(is_array($argument) && !empty($argument[0])){
					$this->irc->deop($channel, $argument[0]);
				}else{
					$this->irc->deop($channel, $user);
				}
			break;
			case $prefix."voice":
				if(is_array($argument) && !empty($argument[0])){
					$this->irc->voice($channel, $argument[0]);
				}else{
					$this->irc->voice($channel, $user);
				}
			break;
			case $prefix."devoice":
				if(is_array($argument) && !empty($argument[0])){
					$this->irc->devoice($channel, $argument[0]);
				}else{
					$this->irc->devoice($channel, $user);
				}
			case $prefix."mute":
				if(is_array($argument) && !empty($argument[0])){
					$this->irc->mute($channel, $argument[0]);
				}else{
					$this->irc->mute($channel, $user);
				}
			break;
			case $prefix."unmute":
				if(is_array($argument) && !empty($argument[0])){
					$this->irc->unmute($channel, $argument[0]);
				}else{
					$this->irc->unmute($channel, $user);
				}
			break;
			case $prefix."kick":
				if(is_array($argument) && !empty($argument[0])){
					$this->irc->kick($channel, $argument[0]);
				}else{
					$this->irc->kick($channel, $user);
				}
			break;
			case $prefix."kickban":
				if(is_array($argument) && !empty($argument[0])){
					$this->irc->ban($channel, $argument[0]);
					$this->irc->kick($channel, $argument[0]);
				}
			break;
			case $prefix."topic":
				if(is_array($argument)){
					$topic = "";
					foreach($argument as $line){
						$topic .= $line;
						$topic .= " ";
					}
					$this->irc->setTopic($channel, $topic);
				}
			break;
			case $prefix."say":
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
					$this->irc->sendMessage($channel, $this->irc->getCommandUsage("say", USER_LEVEL_OWNER));
				}
			break;
			case $prefix."join":
				if(is_array($argument) && !empty($argument[0])){
					$this->irc->send("JOIN", $argument[0]);
				}
			break;
			case $prefix."part":
				if(is_array($argument) && !empty($argument[0])){
					$this->irc->send("PART", $argument[0]);
				}
			break;
			case $prefix."owners":
				$msg = "Owners: ";
				foreach($this->irc->owners as $owner => $hostmask){
					$msg .= $owner;
					$msg .= " ";
				}
				$this->irc->sendMessage($channel, $msg);
			break;
			case $prefix."moderators":
				$msg = "Moderators: ";
				foreach($this->irc->moderators as $moderator => $hostmask){
					$msg .= $moderator;
					$msg .= " ";
				}
				$this->irc->sendMessage($channel, $msg);
			break;
		}
	}
}
