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
	public function kick($channel, $user){
		$this->irc->send("KICK", "$channel $user :Your behavior is not conducive to the desired environment.");
	}
	public function onCommand($message, $command, $user, $channel, $hostmask){
		$prefix = $this->irc->prefix;
		$count = 1;
		$argument = explode(" ", trim(str_replace($command, "", $message, $count)));
		if(!array_key_exists($user, $this->irc->owners)){;
			return;
		}
		if($this->irc->owners[$user] !== $hostmask){
			return;
		}
		switch($command){
			case $prefix."op":
				if(is_array($argument) && $argument[0]){
					$this->irc->send("MODE", "$channel +o ".$argument[0]."");
				}else{
					$this->irc->send("MODE", "$channel +o $user");
				}
			break;
			case $prefix."deop":
				if(is_array($argument) && $argument[0]){
					$this->irc->send("MODE", "$channel -o ".$argument[0]."");
				}else{
					$this->irc->send("MODE", "$channel -o $user");
				}
			break;
			case $prefix."voice":
				if(is_array($argument) && $argument[0]){
					$this->irc->send("MODE", "$channel +v ".$argument[0]."");
				}else{
					$this->irc->send("MODE", "$channel +v $user");
				}
			break;
			case $prefix."devoice":
				if(is_array($argument) && $argument[0]){
					$this->irc->send("MODE", "$channel -v ".$argument[0]."");
				}else{
					$this->irc->send("MODE", "$channel -v $user");
				}
			break;
			case $prefix."kick":
				if(is_array($argument) && $argument[0]){
					$this->kick($channel, $argument[0]);
				}else{
					$this->kick($channel, $user);
				}
			break;
			case $prefix."kickban":
				if(is_array($argument) && $argument[0]){
					$this->irc->send("MODE", "$channel +b ".$argument[0]."!*@");
					$this->kick($channel, $argument[0]);
				}
			break;
			case $prefix."topic":
				if(is_array($argument) && $argument[0]){
					$this->irc->send("TOPIC", $channel." ".$argument[0]);
				}
			break;
			case $prefix."join":
				if(is_array($argument) && $argument[0]){
					$this->irc->send("JOIN", $argument[0]);
				}
			break;
			case $prefix."part":
				if(is_array($argument) && $argument[0]){
					$this->irc->send("PART", $argument[0]);
				}
			break;
		}
	}
}