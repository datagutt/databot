<?php
class IRC{
	public $server = "irc.freenode.net",
		$port = 6667,
		$nick = "Bot",
		$name = "Realname",
		$password,
		$users = array(),
		$channels = array();
	public $sock;
	private $ex;
	public function __construct($bot){
		$this->bot = $bot;
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
	public function send($action = "CTCP", $arg){
		if($this->sock){
			$output = "$action $arg\n";

			// anti flood
			if ($this->bot->delay_until > microtime(true)) {
				time_sleep_until($this->bot->delay_until);
			}

			fwrite($this->sock, $output);
			$this->bot->delay_until = microtime(true)+$this->bot->delay;
		}else{
			throw new Exception("Not connected to server");
		}
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
	public function invite($channel, $user){
		$this->send("INVITE", "$user :$channel");
	}
	public function nick($nick){
		$this->send("NICK", "$nick");
	}
	public function join($channel){
		$this->users[$channel] = array();
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
		$this->bot->log("[TOPIC] $channel topic set to $topic by command", LOG_LEVEL_CHAT);
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
	public function init(){
		$this->send("USER", "".$this->nick." Databot Databot :".$this->name."");
		$this->nick($this->nick);
		if(!empty($this->password)){
			$this->send("NS", "IDENTIFY ".$this->password."");
		}else{
			foreach($this->channels as $channel){
				$this->join($channel);
			}
		}
	}
	public function parse($data){
		// Trimming
		if(substr($data, 0, 1) == ":"){
			$data = substr($data, 1);
		}
		$data = trim($data);

		echo $data."\n";

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
		if((preg_match("/You are now identified|is now your displayed host|No such nick|password accepted -- you are now recognized/", $data)) && $hostname == "services."){
			foreach($this->channels as $channel){
				$this->join($channel);
			}
		}
		//ugly 
		if(!isset($this->ex[1])){
			return;
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
					$this->users[$channel][$user] = $hostmask;
				}
				$this->bot->log("[JOIN] $user joined $channel", LOG_LEVEL_CHAT);
				$this->bot->triggerEvent("join", $passedVars);
			break;
			case "PART":
				// Remove from users array
				if(array_key_exists($user, $this->users[$channel])){
					unset($this->users[$channel][$user]);
				}
				$this->bot->log("[PART] $user parted $channel", LOG_LEVEL_CHAT);
				$this->bot->triggerEvent("part", $passedVars);
			break;
			case "NICK":
				$new = $channel;

				$passedVars = array(
					"user" => $user,
					"new" => $new,
					"hostmask" => $hostmask
				);

				// Rename the user in the users array
				foreach($this->users as $channel => $users){
					$this->users[$channel][$new] = $this->users[$channel][$user];
					unset($this->users[$channel][$user]);
				}
				$this->bot->log("[NICK] $user changed nick to $new", LOG_LEVEL_CHAT);
				$this->bot->triggerEvent("nick", $passedVars);
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
					$c = substr($command, 0, strlen($this->bot->prefix));
					if($c == $this->bot->prefix){
						$passedVars["command"] = substr($command, strlen($this->bot->prefix));
						$this->bot->log("[CMD] $user: $message", LOG_LEVEL_CHAT);
						$this->bot->triggerEvent("command", $passedVars);
					}else if(!empty($user)){
						$this->bot->log("[MSG] $user: $message", LOG_LEVEL_CHAT);
						$this->bot->triggerEvent("message", $passedVars);
					}
				}
			break;
				case "MODE":
				$this->bot->triggerEvent("mode", $passedVars);
			break;
			case "TOPIC":
				$this->bot->log("[TOPIC] $channel topic set to $message by $user", LOG_LEVEL_CHAT);
				$this->bot->triggerEvent("topic", $passedVars);
			break;
			case "KICK":
				// Remove from users array
				if(array_key_exists($user, $this->users[$channel])){
					unset($this->users[$channel][$user]);
				}
				$this->bot->log("[KICK] $user got kicked from $channel", LOG_LEVEL_CHAT);
				$this->bot->triggerEvent("kick", $passedVars);
			break;
			// User list on joining channel
			case "353":
				$users = explode(":", $message);
				$users = explode(" ", $users[1]);
				foreach($users as $user){
					$user = preg_replace("/^[^A-}]+/", "", $user);
					// Do not add ourselves
					if($user !== $this->nick){
						$this->users[$channel][$user] = $user;
						// Send a request for the hostmask and catch it later
						$this->send("USERHOST", $user);
					}
				}
			break;
			// User hosts
			case "302":
				$userhost = explode("=", $message);
				$user = $userhost[0];
				if($user !== $this->nick){
					// Remove the +/- away status
					$hostmask = substr($userhost[1], 1);
					$hostname = explode("@", $hostmask);
					foreach($this->users as $channel => $users){
						$this->users[$channel][$user] = $hostmask;
					}
					// Do not add services
					if($hostname[1] == "services."){
						foreach($this->users as $channel => $users){
							unset($this->users[$channel][$user]);
						}
					}
				}
			break;
			default:
				$this->bot->triggerEvent($this->ex[1], $passedVars);
			break;
		}
	}
}
