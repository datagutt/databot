<?php
class Kickfight_Plugin extends Base_Plugin {
	public $autoOP = true;
	public function onJoin($message, $command, $user, $channel, $hostmask){
		if($this->autoOP){
			// quick hack, channel thingy is fucked up so i just choose 1st channel
			$channel = $this->irc->channels[0];
			if($user == $this->irc->nick){
				return;
			}
			$this->irc->send("MODE $channel +o $user");
		}
	}
	public function onCommand($message, $command, $user, $channel, $hostmask){
		if($command == $this->irc->prefix."op"){
			$this->irc->send("MODE", "$channel +o $user");
		}
		if($command == $this->irc->prefix."autoOP"){
			if(!array_key_exists($user, $this->irc->owners)){
				$this->irc->send("KICK", "$channel $user :Your not a owner, stupid!");
				return;
			}
			if($this->irc->owners[$user] !== $hostmask){
				$this->irc->send("KICK", "$channel $user :Your not a owner, stupid!");
				return;
			}
			$this->autoOP = !$this->autoOP;
			$this->irc->sendMessage("AutoOP level: ".($this->autoOP ? "1" : "0"), $channel);
		}
	}
}