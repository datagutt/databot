<?php
class Google_Plugin extends Base_Plugin {
	public function setup(){
		$this->bot->addCommand("google", "Google the keyword", "<keyword>", USER_LEVEL_GLOBAL);
	}
	public function onCommand($message, $command, $user, $channel){
		if($command == "google"){
			$keywords = trim(substr($message, strlen($this->bot->prefix.$command)));
			$keywords = str_replace(" ", "+", $keywords);
			if(empty($keywords)){
				$this->irc->sendMessage($channel, "Please input a keyword after the command");
				return;
			}
			$googleBaseUrl = "http://ajax.googleapis.com/ajax/services/search/web";
			$googleBaseQuery = "?v=1.0&q=";
			$googleFullUrl = $googleBaseUrl . $googleBaseQuery . $keywords; 
			$curlObject = curl_init();
			curl_setopt($curlObject,CURLOPT_URL, $googleFullUrl);
			curl_setopt($curlObject,CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curlObject,CURLOPT_HEADER, false);
    			$returnGoogleSearch = curl_exec($curlObject);
    			curl_close($curlObject);
			$returnGoogleSearch = json_decode($returnGoogleSearch, true);
			if(!isset($returnGoogleSearch["responseData"]["results"])){
				$this->irc->sendMessage($channel, "No results found");
				return;
			}
   			$first = $returnGoogleSearch["responseData"]["results"][0];
			$this->irc->sendMessage($channel, strip_tags($first["title"]) . " - ".urldecode($first["url"]));
		}
	}
}
