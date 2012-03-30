<?php
/**
 * Класс обеспечивает удобную загрузку файлов на сервер, используется в классе CKSFilesObject, а также в интерфейсах
 * где необходима простая загрузка файлов на сервер. Наследует от класса CKSBaseObject
 * @author BlaDe39 <blade39@kolosstudio.ru>, Dmitry Konev <d.konev@kolosstudio.ru>
 * @version 1.0
 * @since 25.01.2012
 */
class CKSFileUploader extends CKSBaseObject {
	protected $sField;
	protected $mModuleID;
	protected $filename;
	protected $extension;
	protected $sRootDir;
	protected $sDescription;
	protected $nOldFile;
	protected $arFileData;

	/**
	 * Конструктор класса, инициализирует внутренние поля и подготавливает класс к загрузке
	 * @param string
	 * @param string
	 */
	function __construct($sFieldName,$mModuleID='DUMMY')
	{
		$this->sField=$sFieldName;
		$this->mModuleID=$mModuleID;
		$this->sRootDir='bx';
		if(!array_key_exists($this->mModuleID,$_SESSION))
			$_SESSION[$this->mModuleID]=array();
	}

	function SetFileData($arFile)
	{
		$this->arFileData=$arFile;
	}
	
	/**
	 * Метод позволяет установить описание файла
	 * @param string
	 * @return void
	 */
	function SetDescription($sDescription)
	{
		$this->sDescription=$sDescription;
	}

	/**
	 * Старый файл, который необходимо затереть
	 * @param int
	 * @return void
	 */
	function SetOldFile($nOldFile)
	{
		$this->nOldFile=$nOldFile;
	}
	
	/**
	 * Метод позволяет установить путь загрузки файла
	 * @param string
	 * @return void
	 */
	function SetRootDir($sPath)
	{
		$this->sRootDir=$sPath;
	}

	/**
	 * Метод позволяет получить текущее значение пути загрузки сайта
	 * @param void
	 * @return string
	 */
	function GetRootDir()
	{
		return $this->sRootDir;
	}
	
	/**
	 * Метод проверяет загрузил ли пользователь файл на сервер
	 * @param void
	 * @return bool
	 */
	private function HasUploadData()
	{
		if (array_key_exists($this->sField, $_FILES))
		{
			if($_FILES[$this->sField]['error']==UPLOAD_ERR_OK)
			{
				if($_FILES[$this->sField]['size'] > 0)
					return true;
			}
		}
		return false;
	}

	/**
	 * Метод обеспечивает проверку загруженности файла
	 * @param void
	 * @return bool
	 */
	function IsReady()
	{
		if (array_key_exists($this->sField, $_FILES))
		{
			if($_FILES[$this->sField]['error']==UPLOAD_ERR_OK)
			{
				if($_FILES[$this->sField]['size'] > 0)
					return true;
				else
					return false;
			}
			elseif($_FILES[$this->sField]['error']==UPLOAD_ERR_NO_FILE && array_key_exists($this->sField,$_SESSION[$this->mModuleID]))
				return true;
			elseif($_FILES[$this->sField]['error']==UPLOAD_ERR_NO_FILE)
				return false;
			elseif($_FILES[$this->sField]['error']==UPLOAD_ERR_INI_SIZE)
				return false;
			else
				return false;
		}
		return false;
	}

	/**
	 * Метод возвращает имя файла если он был загружен, или false если файл не был загружен
	 * @param void
	 * @return string || bool
	 */
	function GetFileName()
	{
		if($this->IsReady())
			return $_FILES[$this->sField]['name'];
		return false;
	}
	
	/**
	 * Метод выполняет загрузку нового файла
	 * @param string - путь, куда сохранять файл (относительно директории /uploads)
	 * @return string || bool
	 */
	function Upload()
	{
		if(!$this->arFileData)
		{
			if(!$this->HasUploadData() && array_key_exists($this->mModuleID,$_SESSION) && array_key_exists($this->sField,$_SESSION[$this->mModuleID]))
				return $_SESSION[$this->mModuleID][$this->sField];

			$arFile=$_FILES;
		}
		else
			$arFile=$this->arFileData;

		return $this->_Save($arFile);
	}

	private function _Save($arFile)
	{
		$arFile['MODULE_ID']=$this->mModuleID;

		if($this->sDescription)
			$arFile['DESCRIPTION']=$this->sDescription;
			
		if($this->nOldFile){
			$arFile['old_file']=$this->nOldFile;
			$arFile['del']='Y';
		}

		$nFileId=CFile::SaveFile($arFile, $this->sRootDir);

		$nFileId=intval($nFileId);
		if($nFileId>0)
			return $nFileId;

		return false;
	}

	function MultiUpload()
	{
		$arFiles=$this->PrepareMultiData();
		if(!$arFiles)
			return false;

		$arFileIds=array();
		foreach($arFiles as $nKey=>$arFile){
			$arFileIds[$nKey]=$this->_Save($arFile);
		}

		return $arFileIds;
	}

	function PrepareMultiData()
	{
		$arFiles=$_FILES[$this->sField];
		$arTemp=array();
		foreach($arFiles as $sKey=>$arData){
			if(!is_array($arData))
				return false;
			foreach($arData as $nKey=>$sValue)
				$arTemp[$nKey][$sKey]=$sValue;
		}
		return $arTemp;
	}

	/**
	 * Метод выполняет очистку кэша загрузки файла.
	 * @param void
	 * @return void
	 */
	function UploadDone()
	{
		unset($_SESSION[$this->mModuleID][$this->sField]);
	}
}