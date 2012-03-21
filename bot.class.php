<?php
// Bot version
define("VERSION", "0.4");
// Bot name used in VERSION (not nick)
define("BOT", "Databot");

// General bot logs
define("LOG_LEVEL_BOT", 1);
// General irc logs
define("LOG_LEVEL_IRC", 2);
// Events in channels
define("LOG_LEVEL_CHAT", 3);

// User levels
define("USER_LEVEL_GLOBAL", 1);
define("USER_LEVEL_MOD", 2);
define("USER_LEVEL_OWNER", 3);
class Bot {
	public $start_time = 0;
	public $delay_until = 0;
	public $delay = 0.3;
	public $channels = array();
	public $owners = array();
	public $moderators = array();
	public $commands = array();
	public $users = array();
	public $prefix = "@";
	public $loop = 10;
	private $logLevel = LOG_LEVEL_IRC;
	private $sock, $ex, $loopCount, $plugins = array(),
		$loadedPlugins = array();
	public function __construct($bot_config, $irc_config){
		@require("irc.class.php");
		$this->irc = new IRC($this);
		$this->log("Starting Databot...", LOG_LEVEL_BOT);
		$this->start_time = microtime(true);
		$this->log("Parsing config...", LOG_LEVEL_BOT);
		foreach($bot_config as $key => $setting){
			$this->$key = $setting;
		}
		foreach($irc_config as $key => $setting){
			$this->irc->$key = $setting;
		}
		$this->log("Loading plugins...", LOG_LEVEL_BOT);
		$this->loadPlugins($this->plugins);
	}
	public function log($message, $level){
		if($level <= $this->logLevel){
			echo $message."\r\n";
		}
	}
	public function loadPlugins($plugins){
		// Load base plugin (for help commands)
		// This is really ugly…
		@require("plugins/base.plugin.php");
		$plugins = array_reverse($plugins, true);
		$plugins["Base_Plugin"] = "base";
		$plugins = $this->plugins = array_reverse($plugins, true);
		$this->loadedPlugins["Base_Plugin"] = new Base_Plugin($this->irc->sock, $this, $this->irc);
		foreach($plugins as $class => $plugin){
			@require_once("plugins/$plugin.plugin.php");
			$this->loadedPlugins[$class] = new $class($this->irc->sock, $this, $this->irc);
		}
	}
	public function setPluginProperty($plugin, $property, $value){
		if(empty($plugin) || empty($property)){
			trigger_error("setPluginProperty: No plugin, property and value given", E_USER_WARNING);
			return;
		}
		if(array_key_exists($plugin, $this->loadedPlugins)){
			$this->loadedPlugins[$plugin]->$property = $value;
		}
	}
	public function triggerEvent($event, $vars = array()){
		if($this->irc->sock){
			foreach($this->plugins as $class => $plugin){
				$func = "onDefault";
				switch($event){
					case "join":
						$func = "onJoin";
					break;
					case "part":
						$func = "onPart";
					break;
					case "nick":
						$func = "onNick";
					break;
					case "message":
						$func = "onMessage";
					break;
					case "command":
						$func = "onCommand";
					break;
					case "text":
						$func = "onText";
					break;
					case "topic":
						$func = "onTopic";
					break;
					case "kick":
						$func = "onKick";
					break;
					case "set":
						$func = "onSet";
					break;
				}
				if($func !== "onDefault"){
					call_user_func_array(array($this->loadedPlugins[$class], $func), $vars); 
				}
				
			}
		}else{
			throw new Exception("Not connected to server");
		}
	}
	public function addCommand($command, $description, $usage, $level = USER_LEVEL_GLOBAL){
		$this->commands[$command] = array();
		$this->commands[$command][$level] = array();
		$this->commands[$command][$level]["description"] = $description;
		$this->commands[$command][$level]["usage"] = $usage;
	}
	public function getCommandMinimumLevel($command){
		if($this->isCommand($command, USER_LEVEL_OWNER)){
			$levels = array_keys($this->commands[$command]);
			return min($levels);
		}
	}
	public function getCommandDescription($command, $level = USER_LEVEL_GLOBAL){
		if($this->isCommand($command, $level)){
			for($targetLevel = $level; $targetLevel > 0; $targetLevel--){
				if(array_key_exists($targetLevel, $this->commands[$command])){
					return $this->commands[$command][$targetLevel]["description"];
				}
			}
		}
	}
	public function getCommandUsage($command, $level = USER_LEVEL_GLOBAL){
		if($this->isCommand($command, $level)){
			for($targetLevel = $level; $targetLevel > 0; $targetLevel--){
				if(array_key_exists($targetLevel, $this->commands[$command])){
					return $this->commands[$command][$targetLevel]["usage"];
				}
			}
		}
	}
	public function isCommand($command, $level = USER_LEVEL_GLOBAL){
		if(array_key_exists($command, $this->commands)){
			$levels = array_keys($this->commands[$command]);
			if(min($levels) <= $level){
				return true;
			}
		}
		return false;
	}
	public function getUserLevel($user, $hostname){
		if($this->isOwner($user, $hostname)){
			return USER_LEVEL_OWNER;
		}elseif($this->isModerator($user, $hostname)){
			return USER_LEVEL_MOD;
		}else{
			return USER_LEVEL_GLOBAL;
		}
	}
	public function isOwner($user, $hostname){
		return array_key_exists($user, $this->owners) && $this->owners[$user] == $hostname;
	}
	public function isModerator($user, $hostname){
		return array_key_exists($user, $this->moderators) && $this->moderators[$user] == $hostname;
	}

	public function run(){
		if(!$this->irc->sock){
			$this->log("Connecting to ".$this->irc->server."...", LOG_LEVEL_IRC);
			$this->irc->connect($this->irc->server, $this->irc->port);
		}
		$this->log("Connected to ".$this->irc->server."", LOG_LEVEL_IRC);
		$this->irc->init();
		while(!feof($this->irc->sock)){
			// Looping plugins
			$this->loopCount++;
			if($this->loop > 0 && ($this->loopCount % $this->loop == 0)){
				$this->loopCount = 0;
				foreach($this->plugins as $class => $plugin){
					call_user_func(array($this->loadedPlugins[$class], "onLoop"));
				}
			}

			// Read data
			$data = fgets($this->irc->sock, 8192);
			if(empty($data)){
				continue;
			}else{
				$this->irc->parse($data);
				flush();
			}
		}
	}
}
