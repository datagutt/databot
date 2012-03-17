<?php
error_reporting(E_ALL); 
ini_set("display_errors", 1); 
@require("bot.class.php");
@require("config.local.php");
$bot = new Bot($conf);
$bot->run();
