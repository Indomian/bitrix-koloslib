<?php
global $DB, $MESS, $APPLICATION;
IncludeModuleLangFile(__FILE__);

if(!defined('KOLOSLIB'))
{
	define('KOLOSLIB',1);
	define('NOTIFY_WARNING','N');
	define('NOTIFY_MESSAGE','N');
	define('KOLOSLIB_DEBUG',0);
	define('KOLOSLIB_LOG_DB_ERRORS',1);
	define('KOLOSLIB_DEBUG_QUERY',0);
	define('KS_ROOT_DIR',$_SERVER['DOCUMENT_ROOT']);
	define('KS_MODULES_DIR',KS_ROOT_DIR.'/bitrix/modules');

	require_once KS_MODULES_DIR.'/koloslib/classes/mysql/class.CKSBitrixDatabase.php';
	require_once KS_MODULES_DIR.'/koloslib/classes/mysql/class.CKSDBController.php';
	global $ks_db;
	if(!($ks_db instanceof CKSBitrixDatabase))
		$ks_db=new CKSBitrixDatabase();

	require_once KS_MODULES_DIR.'/koloslib/classes/general/functions.php';
	require_once KS_MODULES_DIR.'/koloslib/classes/general/class.CKSError.php';
	require_once KS_MODULES_DIR.'/koloslib/classes/general/class.CKSDataError.php';
	require_once KS_MODULES_DIR.'/koloslib/classes/general/interface.IKSListObject.php';
	require_once KS_MODULES_DIR.'/koloslib/classes/general/class.CKSBaseObject.php';
	require_once KS_MODULES_DIR.'/koloslib/classes/general/class.CKSObject.php';
	require_once KS_MODULES_DIR.'/koloslib/classes/general/class.CKSModules.php';
	require_once KS_MODULES_DIR.'/koloslib/classes/general/class.CKSFileUploader.php';
}