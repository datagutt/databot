<?php
define("VERSION", "0.2");
class Bot {
	public $start_time = 0;
	public $server, 
		$port = 6667,
		$name = "Realname",
		$prefix = "@",
		$password;
	public $channels = array();
	public $owners = array();
	public $nick = "Bot";
	public $commands = array();
	public $users = array();
	private $sock, $ex, $plugins = array(), 
		$loadedPlugins = array();
	public function __construct($config){
		$this->start_time = microtime(true);
		foreach($config as $key => $setting){
			$this->$key = $setting;
		}
		$this->loadPlugins($this->plugins);
	}
	public function connect(){
		if(!empty($this->server)){
			$this->sock = fsockopen($this->server, $this->port);
		}else{
			throw new Exception("No server is defined");
		}
	}
	public function disconnect(){
		if($this->sock){
			$this->send("QUIT", ":Disconnected");
			fclose($this->sock);
		}else{
			throw new Exception("Your not connected to a server!");
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
			throw new Exception("Your not connected to a server!");
		}
	}
	public function send($action = "CTCP", $arg){
		if($this->sock){
			$output = "$action $arg\n";
			fwrite($this->sock, $output);
		}else{
			throw new Exception("Your not connected to a server!");
		}
	}
	public function sendMessage($message = "", $channel){
		if(empty($message) || empty($channel)){
			throw new Exception("No message or channel given");
		}
		$this->send("PRIVMSG", "$channel :$message");
	}
	public function joinChannels(){
		foreach($this->channels as $channel){
			$this->send("JOIN", $channel);
		}
	}
	public function run(){
		if(!$this->sock){
			$this->connect();
		}
		$this->send("USER", "".$this->nick." Databot Databot :".$this->name."");
		$this->send("NICK", $this->nick);
		if(!empty($this->password)){
			$this->send("NS", "IDENTIFY ".$this->password."");
		}else{
			$this->joinChannels();
		}
		while(!feof($this->sock)){
			usleep(100000);
			while($data = fgets($this->sock, 128)){
				$this->ex = explode(" ", $data);
				list($user, $hostmask, $split, $command, $message, $channel) = "";
				$size = sizeof($this->ex);
				$hostmask = explode('!', $data);
				if(isset($hostmask[1])){
					$hostmask = explode('@', $hostmask[1]);
					if(isset($hostmask[1])){
						$hostmask = explode(' ', $hostmask[1]);
						$hostmask = $hostmask[0];
					}
				}
				if(empty($hostmask) || is_array($hostmask)){
					$hostmask = "";
				}
				$user = str_replace(":", "", strstr($this->ex[0], '!', true));
				if(isset($this->ex[3])){
					$split = explode(':', $this->ex[3], 2);
					// start of message
					$message = $command = count($split) > 1 ? trim($split[1]) : "";
					if($message == "VERSION"){
						$this->send("NOTICE", "VERSION $user DataBot :".VERSION."");
					}
				}
				for($i = 4; isset($this->ex[$i]); $i++){
					if($i < $size){
						$message .= " ";
					}
					$message .= $this->ex[$i];
				}
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
					$this->joinChannels();
				}
				//ugly 
				if(!isset($this->ex[1])){
					break;
				}
				switch($this->ex[1]){
					case "JOIN":
						$this->users[$user] = $user;
						$this->triggerEvent("join", $passedVars);
					break;
					case "PART":
						if(in_array($user, $this->users)){
							unset($this->users[$user]);
						}
						$this->triggerEvent("part", $passedVars);
					break;
					case "PRIVMSG":
						if(!empty($command) && in_array($channel, $this->channels)){
							// if theres a prefix at the start of the message, its a command
							$c = substr($command, 0, strlen($this->prefix));
							if($c == $this->prefix){
								$this->triggerEvent("command", $passedVars);
							}else if(!empty($user)){
								$this->triggerEvent("message", $passedVars);
							}
						}
					break;
					case "MODE":
						$this->triggerEvent("mode", $passedVars);
					break;
					case "TOPIC":
						$this->triggerEvent("topic", $passedVars);
					break;
					case "KICK":
						// remove from users array
						if(in_array($user, $this->users)){
							unset($this->users[$user]);
						}
						$this->triggerEvent("kick", $passedVars);
					break;
					case "353":
						$users = explode(" ", $message);
						foreach($users as $user){
							$user = preg_replace("/^[^A-}]+/", "", $user);
							// If nick is not the bots, put it in the users array
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
