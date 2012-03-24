<?php
define("DEFAULT_DOWNLOAD_LINK", "http://get.cm/");
class Troll_Plugin extends Base_Plugin {
	public $devices = array();
	public function setup(){
		// Commands
		$this->irc->addCommand("flame", "Flames", "[<user>]", USER_LEVEL_MOD);
	}
	public function onMessage($message, $command, $user, $channel, $hostmask){
		if(preg_match("/\/b\//i", $message)){
			$this->irc->kick($channel, $user, "NOBODY TALKS ABOUT /B/");
		}
	}
	public function onCommand($message, $command, $user, $channel, $hostmask){
		$count = 1;
		$argument = explode(" ", trim(str_replace($this->irc->prefix.$command, "", $message, $count)));
		$msg = "";
		switch($command){

		}
		if(!empty($msg)){
			$this->irc->sendMessage($channel, $msg);
		}
	}
}
