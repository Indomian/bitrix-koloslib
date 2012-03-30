<?php
if(!is_object($GLOBALS["USER_FIELD_MANAGER"]))
	return false;
IncludeModuleLangFile(__FILE__);
$arMenu=array(
	'parent_menu'=>'global_menu_services',
	'sort'=>1000,
	'url'=>'kolos_dummy_nothing.php',
	'text'=>GetMessage('KOLOS_DUMMY_MENU_TEXT'),
	'title'=>GetMessage('KOLOS_DUMMY_MENU_TITLE'),
	'icon'=>'blog_menu_icon',
	'page_icon'=>'blog_page_icon',
	'module_id'=>'kolos_dummy'
);

return $arMenu;