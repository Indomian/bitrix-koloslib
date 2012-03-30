<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$arComponentDescription = array(
	"NAME" => GetMessage("K_TDW_TITLE"),
	"DESCRIPTION" => GetMessage("K_TDW_DESCRIPTION"),
	"ICON" => "/images/icon.gif",
	"PATH" => array(
		"ID" => "content",
		"CHILD" => array(
			"ID" => "kopeyker",
			"NAME" => GetMessage('K_MENU_TITLE')
		)
	)
);