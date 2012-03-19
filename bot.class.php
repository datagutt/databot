<?php
// Bot version
define("VERSION", "0.3");
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
	public $last_send_time = 0;
	public $server,
		$port = 6667,
		$name = "Realname",
		$prefix = "@",
		$delay = 1,
		$password;
	public $channels = array();
	public $owners = array();
	public $moderators = array();
	public $nick = "Bot";
	public $commands = array();
	public $users = array();
	public $loop = 10;
	private $logLevel = LOG_LEVEL_IRC;
	private $sock, $ex, $loopCount, $plugins = array(),
		$loadedPlugins = array();
	public function __construct($config){
		$this->log("Starting Databot...", LOG_LEVEL_BOT);
		$this->start_time = microtime(true);
		$this->log("Parsing config...", LOG_LEVEL_BOT);
		foreach($config as $key => $setting){
			$this->$key = $setting;
		}
		$this->log("Loading plugins...", LOG_LEVEL_BOT);
		$this->loadPlugins($this->plugins);
	}
	public function connect($server, $port){
		if(!empty($server) && !empty($port)){
			$this->sock = fsockopen($server, $port);
			stream_set_timeout($this->sock, 0, 100000);
		}else{
			throw new Exception("No server is defined");
		}
	}
	public function disconnect(){
		if($this->sock){
			$this->send("QUIT", ":Disconnected");
			fclose($this->sock);
		}else{
			throw new Exception("Not connected to server");
		}
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
		$this->loadedPlugins["Base_Plugin"] = new Base_Plugin($this->sock, $this);
		foreach($plugins as $class => $plugin){
			@require_once("plugins/$plugin.plugin.php");
			$this->loadedPlugins[$class] = new $class($this->sock, $this);
		}
	}
	public function triggerEvent($event, $vars = array()){
		if($this->sock){
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
				}
				if($func !== "onDefault"){
					call_user_func_array(array($this->loadedPlugins[$class], $func), $vars); 
				}
				
			}
		}else{
			throw new Exception("Not connected to server");
		}
	}
	public function send($action = "CTCP", $arg){
		if($this->sock){
			$output = "$action $arg\n";
            
			// anti flood
			if ($this->last_send_time > (time() - $this->delay)) {
				sleep($this->delay);
			}

			fwrite($this->sock, $output);
			$this->last_send_time = time();
		}else{
			throw new Exception("Not connected to server");
		}
	}
	public function sendMessage($target, $message = ""){
		if(empty($message) || empty($target)){
			trigger_error("sendMessage: No message or target given", E_USER_WARNING);
			return;
		}
		$this->send("PRIVMSG", "$target :$message");
	}
	public function sendNotice($target, $message = ""){
		if(empty($message) || empty($target)){
			trigger_error("sendNotice: No message or channel given", E_USER_WARNING);
			return;
		}
		$this->send("NOTICE", "$target :$message");
	}
	public function addCommand($command, $description, $usage, $level = USER_LEVEL_GLOBAL){
		$this->commands[$command] = array();
		$this->commands[$command][$level] = array();
		$this->commands[$command][$level]["description"] = $description;
		$this->commands[$command][$level]["usage"] = $usage;
	}
	public function getCommandUsage($command, $level = USER_LEVEL_GLOBAL){
		if($this->isCommand($command, $level)){
			for($targetLevel = $level; $targetLevel > 0; $targetLevel--){
				if(array_key_exists($targetLevel, $this->commands[$command])){
					return "USAGE: ".$this->commands[$command][$targetLevel]["usage"];
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
	public function kick($channel, $user, $message = ""){
		$this->send("KICK", "$channel $user :$message");
	}
	public function op($channel, $user){
		$this->send("MODE", "$channel +o $user");
	}
	public function deop($channel, $user){
		$this->send("MODE", "$channel -o $user");
	}
	public function voice($channel, $user){
		$this->send("MODE", "$channel +v $user");
	}
	public function devoice($channel, $user){
		$this->send("MODE", "$channel -v $user");
	}
	public function mute($channel, $user){
		$this->send("MODE", "$channel +q $user");
	}
	public function unmute($channel, $user){
		$this->send("MODE", "$channel -q $user");
	}
	public function ban($channel, $hostmask){
		$this->send("MODE", "$channel +b $hostmask");
	}
	public function unban($channel, $hostmask){
		$this->send("MODE", "$channel -b $hostmask");
	}
	public function nick($nick){
		$this->send("NICK", "$nick");
	}
	public function join($channel){
		$this->send("JOIN", $channel);
	}
	public function part($channel){
		$this->send("PART", $channel);
	}
	public function setTopic($channel, $topic = "Default"){
		if(empty($channel) || empty($topic)){
			trigger_error("sendTopic: No topic or channel given", E_USER_WARNING);
			return;
		}
		// This needs TOPICLOCK to be set OFF on ChanServ 
		$this->send("TOPIC", "$channel :$topic");
		$this->log("[TOPIC] $channel topic set to $topic by command", LOG_LEVEL_CHAT);
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
		if(!$this->sock){
			$this->log("Connecting to $this->server...", LOG_LEVEL_IRC);
			$this->connect($this->server, $this->port);
		}
		$this->log("Connected to $this->server", LOG_LEVEL_IRC);
		$this->send("USER", "".$this->nick." Databot Databot :".$this->name."");
		$this->send("NICK", $this->nick);
		if(!empty($this->password)){
			$this->send("NS", "IDENTIFY ".$this->password."");
		}else{
			foreach($this->channels as $channel){
				$this->join($channel);
			}
		}
		while(!feof($this->sock)){
			// Looping plugins
			$this->loopCount++;
			if($this->loop > 0 && ($this->loopCount % $this->loop == 0)){
				$this->loopCount = 0;
				foreach($this->plugins as $class => $plugin){
					call_user_func(array($this->loadedPlugins[$class], "onLoop"));
				}
			}

			// Read data
			$data = fgets($this->sock, 8192);
			if(empty($data)){
				continue;
			}else{
				// Trimming
				if(substr($data, 0, 1) == ":"){
					$data = substr($data, 1);
				}
				$data = trim($data);

				$this->ex = explode(" ", $data);
				
				list($user, $hostmask, $hostname, $split, $command, $message, $channel) = "";
				$size = sizeof($this->ex);

				// Hostmask
				$hostmask = explode('!', $this->ex[0]);
				if(isset($hostmask[1])){
					$hostmask = $hostmask[1];
				}
				if(empty($hostmask) || is_array($hostmask)){
					$hostmask = $this->ex[0];
				}else{
					$hostname = explode("@", $hostmask);
					$hostname = $hostname[1];
				}

				// User
				$user = strstr($this->ex[0], '!', true);

				// Message
				if(isset($this->ex[3])){
					$split = explode(':', $this->ex[3], 2);
					// start of message
					$message = $command = count($split) > 1 ? trim($split[1]) : "";
					for($i = 4; isset($this->ex[$i]); $i++){
						$message .= " ";
						$message .= $this->ex[$i];
					}
				}
				$message = trim($message);

				// Channel
				if(isset($this->ex[2])){
					$channel = str_replace(":", "", $this->ex[2]);
				}

				$passedVars = array(
					"message" => $message,
					"command" => $command,
					"user" => $user,
					"channel" => $channel,
					"hostmask" => $hostmask
				);
				if(isset($this->ex[0]) && isset($this->ex[1]) && $this->ex[0] == "PING"){
					$this->send("PONG", $this->ex[1]);
				}
				if((preg_match("/You are now identified|is now your displayed host|No such nick|password accepted -- you are now recognized/", $data))){
					foreach($this->channels as $channel){
						$this->join($channel);
					}
				}
				//ugly 
				if(!isset($this->ex[1])){
					continue;
				}
				$event = $this->ex[1];
				if(!empty($message) && substr($message, 0, 1) == chr(1) && substr($message, -1) == chr(1)){
					$event = "CTCP";
				}

				switch($event){
					case "JOIN":
						// Do not add ourselves or services
						if($user !== $this->nick && $hostname !== "services."){
							// Add to users array
							$this->users[$user] = $hostmask;
						}
						$this->log("[JOIN] $user joined $channel", LOG_LEVEL_CHAT);
						$this->triggerEvent("join", $passedVars);
					break;
					case "PART":
						// Remove from users array
						if(array_key_exists($user, $this->users)){
							unset($this->users[$user]);
						}
						$this->log("[PART] $user parted $channel", LOG_LEVEL_CHAT);
						$this->triggerEvent("part", $passedVars);
					break;
					case "NICK":
						$new = $channel;

						$passedVars = array(
							"user" => $user,
							"new" => $new,
							"hostmask" => $hostmask
						);

						// Rename the user in the users array
						$this->users[$new] = $this->users[$user];
						unset($this->users[$user]);
						$this->log("[NICK] $user changed nick to $new", LOG_LEVEL_CHAT);
						$this->triggerEvent("nick", $passedVars);
						break;
					case "CTCP":
						$message = substr($message, 1, -1);
						if($message == "VERSION"){
							$this->sendNotice($user, "VERSION ".BOT." ".VERSION);
						}
						break;
					case "PRIVMSG":
						if(!empty($command)){
							// If theres a prefix at the start of the message, its a command
							$c = substr($command, 0, strlen($this->prefix));
							if($c == $this->prefix){
								$this->log("[CMD] $user: $message", LOG_LEVEL_CHAT);
								$this->triggerEvent("command", $passedVars);
							}else if(!empty($user)){
								$this->log("[MSG] $user: $message", LOG_LEVEL_CHAT);
								$this->triggerEvent("message", $passedVars);
							}
						}
					break;
					case "MODE":
						$this->triggerEvent("mode", $passedVars);
					break;
					case "TOPIC":
						$this->log("[TOPIC] $channel topic set to $message by $user", LOG_LEVEL_CHAT);
						$this->triggerEvent("topic", $passedVars);
					break;
					case "KICK":
						// Remove from users array
						if(array_key_exists($user, $this->users)){
							unset($this->users[$user]);
						}
						$this->log("[KICK] $user got kicked from $channel", LOG_LEVEL_CHAT);
						$this->triggerEvent("kick", $passedVars);
					break;
					// User list on joining channel
					case "353":
						$users = explode(":", $message);
						$users = explode(" ", $users[1]);
						foreach($users as $user){
							$user = preg_replace("/^[^A-}]+/", "", $user);
							// Do not add ourselves
							if($user !== $this->nick){
								$this->users[$user] = $user;
							}
						}
						break;
					default:
						$this->triggerEvent($this->ex[1], $passedVars);
					break;
				}
				flush();
			}
		}
	}
}
