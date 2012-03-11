<?php
$conf = array(
	"nick" => "KickerBot",
	"name" => "Kick bot",
	"server" => "irc.freenode.net",
	"prefix" => "!",
	"channels" => array("#kickfight"),
	"owners" => array("owner" => "hostmask"),
	"plugins" => array(
		"Kickfight_Plugin" => "kickfight"
	)
);
