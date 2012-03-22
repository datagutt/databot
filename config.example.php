<?php
$bot_conf = array(
	"plugins" => array(
		"OP_Plugin" => "op"
		"CM_Plugin" => "cm"
		"Kickfight_Plugin" => "kickfight"
	),
	"owners" => array(
		"example" => "example@example.org"
	),
	"moderators" => array(
		"example" => "~example@example.org"
	),
	"channels" => array("#example"),
	"prefix" => "!"
);
$irc_conf = array(
	"nick" => "bot",
	"name" => "Bot",
	"server" => "irc.freenode.net",
);