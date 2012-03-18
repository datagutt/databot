<?php
$conf = array(
	"nick" => "bot",
	"name" => "Bot",
	"server" => "irc.freenode.net",
	"prefix" => "!",
	"delay" => "1",
	"channels" => array("#example"),
	"owners" => array(
		"example" => "example@example.org"
	),
	"moderators" => array(
		"example" => "~example@example.org"
	),
	"plugins" => array(
		"OP_Plugin" => "op"
		"CM_Plugin" => "cm"
		"Kickfight_Plugin" => "kickfight"
        
	)
);
