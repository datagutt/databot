<?php
define("DEFAULT_DOWNLOAD_LINK", "http://get.cm/");
class CM_Plugin extends Base_Plugin {
	public $devices = array();
	public function setup(){
		// Commands
		$this->irc->addCommand("supported", "Shows supported devices", "[<manufacturer>]", USER_LEVEL_GLOBAL);
		$this->irc->addCommand("downloads", "Shows download link(s)", "[<device_name>]", USER_LEVEL_GLOBAL);
		$this->irc->addCommand("eta", "Shows a angry message to the user", "", USER_LEVEL_GLOBAL);
		
		// Devices
		$this->addDevice("samsung", "galaxys2","GT-I9100", "http://get.cm/?device=galaxys2");
		$this->addDevice("samsung", "galaxys","GT-I9000", "http://get.cm/?device=galaxysmtd");
		$this->addDevice("samsung", "vibrant","vibrant", "http://get.cm/?device=vibrantmtd");
		$this->addDevice("samsung", "crespo","crespo", "http://get.cm/?device=crespo");
		$this->addDevice("samsung", "maguro","GT-I9250", "http://get.cm/?device=maguro");
		$this->addDevice("samsung", "toro","SCH-I515", "http://get.cm/?device=toro");
		$this->addDevice("samsung", "captivate","SGH-I897", "http://get.cm/?device=captivatemtd");
		$this->addDevice("samsung", "fascinate","SCH-I500", "http://get.cm/?device=fascinatemtd");
	}
	public function addDevice($manufacturer, $device_name, $device_model, $download_link = DEFAULT_DOWNLOAD_LINK){
		if(!array_key_exists($manufacturer, $this->devices)){
			$this->devices[$manufacturer] = array();
		}
		$this->devices[$manufacturer][$device_name] = array();
		$this->devices[$manufacturer][$device_name]["download_link"] = $download_link;
		$this->devices[$manufacturer][$device_name]["device_name"] = $device_name;
		$this->devices[$manufacturer][$device_name]["device_model"] = $device_model;
	}
	public function getDevice($device_name){
		foreach($this->devices as $manufacturer => $devices){
			if(array_key_exists($device_name, $devices)){
				return $this->devices[$manufacturer][$device_name];
			}
		}
	}
	public function getDevices($manufacturer){
		if(!$manufacturer){
			return $this->devices;
		}else{
			if(array_key_exists($manufacturer, $this->devices)){
				return $this->devices[$manufacturer];
			}
		}
		return array();
	}
	public function onCommand($message, $command, $user, $channel, $hostmask){
		$prefix = $this->irc->prefix;
		$count = 1;
		$argument = explode(" ", trim(str_replace($command, "", $message, $count)));
		$msg = "";
		switch($command){
			case $prefix."supported":
				if(is_array($argument) && !empty($argument[0])){	
					$devices = $this->getDevices($argument[0]);
				}else{
					$devices = $this->getDevices();
				}
				if(count($devices) == 0){
					$msg .= "That is not a valid manufacturer!";
				}else{
					$msg .= "Supported devices: ";
				}
				foreach($devices as $device){
					$msg .= $device["device_name"];
					$msg .= " ";
				}
			break;
			case $prefix."downloads":
				if(is_array($argument) && !empty($argument[0])){
					$device = $this->getDevice($argument[0]);
					if(is_array($device)){
						$msg .= "Download at: ";
						$msg .= $device["download_link"];
					}else{
						$msg .= "That is not a valid device name!";
					}
				}else{
					$msg = "Downloads: ". DEFAULT_DOWNLOAD_LINK;
				}
			break;
			case $prefix."eta":
				$msg = "NEVER ask for ETAs.";
			break;
		}
		if(!empty($msg)){
			$this->irc->sendMessage($channel, $msg);
		}
	}
}