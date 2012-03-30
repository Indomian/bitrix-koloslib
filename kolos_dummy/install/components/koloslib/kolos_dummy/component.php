<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("kolos_dummy")){
	ShowError(GetMessage("KOPEYKER_MODULE_NOT_INSTALL"));
	return;
}

IncludeModuleLangFile(__FILE__);

$sMessage=(!empty($arParams['MESSAGE'])) ? $arParams['MESSAGE'] : '';
$arResult['MESSAGE']=GetMessage($sMessage);

$this->IncludeComponentTemplate($componentPage);