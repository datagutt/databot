<?php
class OP_Plugin extends Base_Plugin {
	public function setup(){
		$this->irc->commands["op"] = "op";
		$this->irc->commands["deop"] = "deop";
		$this->irc->commands["voice"] = "voice";
		$this->irc->commands["devoice"] = "devoice";
		$this->irc->commands["kick"] = "kick";
		$this->irc->commands["kickban"] = "kickban";
		$this->irc->commands["topic"] = "topic";
	}
	public function onCommand($message, $command, $user, $channel, $hostmask){
		$prefix = $this->irc->prefix;
		$count = 1;
		$argument = explode(" ", trim(str_replace($command, "", $message, $count)));
		if(!$this->isOwner($user, $hostmask){
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
				if(is_array($argument) && !empty($argument[0])){
					$this->irc->send("TOPIC", $channel." ".$argument[0]);
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
		}
	}
}