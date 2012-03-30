<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$arComponentParameters = array(
 	"GROUPS" => array(
 		"SETTINGS" => array(
 			"NAME" => GetMessage("K_SETTINGS")
 		),
	),
	 "PARAMETERS" => array(
		'MESSAGE'=>array(
			"NAME" => GetMessage("K_KD_MESSAGE"),
			"TYPE" => "LIST",
			"VALUES" => array(
				'K_KD_MESSAGE_HW'=>GetMessage('K_KD_MESSAGE_HW'),
				'K_KD_MESSAGE_BW'=>GetMessage('K_KD_MESSAGE_BW')
			)
		)
	)
);