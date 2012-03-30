<?php
/**
 * Класс реализующей объект выполняющий работу с файлами (загрузку на сервер и хранение адреса файла),
 * наследует от класса CKSObject
 * @author BlaDe39 <blade39@kolosstudio.ru>, Dmitry Konev <d.konev@kolosstudio.ru>
 * @version 1.0
 * @since 25.01.2012
 */
class CKSFilesObject extends CKSObject {
	protected $arFileFields;
	public $sUploadPath;
	private $obUploadManager;

	/**
	 * Конструктор класса, вызывает родительский конструктор, а также инициализирует поля класса
	 * @param string - Название таблицы в БД без префикса
	 * @param string - Директория для загрузки файлов
	 */
	function __construct($sTable='',$sUploadPath='')
	{
		parent::__construct($sTable);
		$this->arFileFields=array();
		$this->sUploadPath=$sUploadPath;
		$this->obUploadManager=false;
	}

	/**
	 * Метод позволяет изменить директорию для загрузки файлов
	 * @param string
	 * @return void
	 */
	function SetUploadPath($path)
	{
		$this->sUploadPath=$path;
	}

	/**
	 * Метод возвращает адрес директории для загрузки файлов
	 * @param void
	 * @return string
	 */
	function GetUploadFolder()
	{
		return $this->sUploadPath;
	}

	/**
	 * Метод добавляет поле в список файловых подлежащих обработке
	 * @param string
	 * @return bool
	 */
	function AddFileField($field)
	{
		if(in_array($field,$this->arFields))
		{
			if(!in_array($field,$this->arFileFields))
				$this->arFileFields[]=$field;
			return true;
		}
		return false;
	}

	/**
	 * Метод выполняет получение значений из $_POST массива, обычно вызывается в случае ошибки
	 * при проверке введённых пользователем значений
	 * @param string - Префикс ключей в массиве со значениями
	 * @param array - Массив со значеними. Необязательный. Если не указан, то значения берутся из массива $_POST
	 * @return array
	 */
	function GetRecordFromPost($prefix='',$data=false)
	{
		global $KS_FS;
		if(!$data) $data=$_POST;
		foreach ($this->arFields as $field)
		{
			if(in_array($field,$this->arFileFields))
			{
				$obUploadManager=new CFileUploader($prefix.$field,$this->sTable);
				if($obUploadManager->IsReady())
				{
					$arResult[$field]=$obUploadManager->Upload($this->sUploadPath.'/'.$this->_GenFileName($obUploadManager->GetFileName()),false);
				}
			}
			else
			{
				$arResult[$field]=$data[$prefix.$field];
			}
		}
		return $arResult;
	}

	/**
	 * Метод выполняет генерацию имени файла в случае необходимости
	 * @param string - Имя файла
	 * @return string
	 */
	private function _GenFileName($filename)
	{
		return md5($filename.time()).'.'.substr($filename,strrpos($filename,'.')+1);
	}

	/**
	 * метод перекрывает родительский, выполняет проверку попадания поля в список файловых полей и
	 * обеспечивает сохранение значения файлового поля в случае необходимости
	 * @param string
	 * @param string
	 * @param string
	 * @param string
	 * @return string
	 */
	protected function _ParseField($prefix,$key,&$input,&$value)
	{
		global $KS_FS;
		$sResult=parent::_ParseField($prefix,$key,$input,$value);
		if(in_array($key,$this->arFileFields))
		{
			$obUploadManager=new CFileUploader($prefix.$key,$this->sTable);
			if($obUploadManager->IsReady())
			{
				$sResult=$obUploadManager->Upload($this->sUploadPath.'/'.$this->_GenFileName($obUploadManager->GetFileName()),false);
				$obUploadManager->UploadDone();
				if($sResult)
				{
					if(!empty($input[$prefix.'id']) && $input[$prefix.'id']!='')
					{
						$arItem=$this->GetRecord(array('id'=>$input[$prefix . 'id']));
						if(is_array($arItem)&&($arItem['id']==$input[$prefix . 'id']))
						{
							if (file_exists(UPLOADS_DIR.$arItem[$key])&&is_file(UPLOADS_DIR.$arItem[$key]))
								unlink(UPLOADS_DIR.$arItem[$key]);
						}
					}
				}
			}
			if (array_key_exists($prefix . $key . '_del', $_REQUEST))
			{
				if($input[$prefix.'id']!='')
				{
					$arItem=$this->GetRecord(array('id'=>$input[$prefix . 'id']));
					if(is_array($arItem)&&($arItem['id']==$input[$prefix . 'id']))
					{
						if (file_exists(UPLOADS_DIR.$arItem[$key]))
							unlink(UPLOADS_DIR.$arItem[$key]);
					}
				}
				$sResult="";
			}
		}
		return $sResult;
	}

	/**
	 * Метод перекрывает родительский и в случае удаления записей, также удаляет связанные с ними файлы
	 * @param array
	 * @return bool
	 */
	function DeleteItems($arFilter)
	{
		$arItems=$this->GetList(array('id'=>'asc'),$arFilter);
		if(is_array($arItems)&&count($arItems)>0)
		{
			foreach($arItems as $key=>$item)
			{
				foreach($this->arFileFields as $field)
				{
					if($item[$field]!='')
					{
						@unlink(UPLOADS_DIR.$item['img']);
					}
				}
			}
			return parent::DeleteItems($arFilter);
		}
		return false;
	}
}