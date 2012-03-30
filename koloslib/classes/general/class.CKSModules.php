<?php
class CKSModules extends CKSBaseObject implements IKSListObject
{
	private static $instance;
	private $arIsModules;
	private $arModules;
	private $bSave;
	private $arFields;
	private $arFieldsChecks;
	private $arGetListResult;
	private $arGetListSort;

	static function get_instance()
	{
		if(!(CKSModules::$instance instanceof CKSModules))
		{
			CKSModules::$instance=new CKSModules();
			CKSModules::$instance->Init();
		}
		return CKSModules::$instance;
	}
	
	/**
	 * Конструктор класса, выполняет стартовый запуск объекта
	 */
	function __construct()
	{
		//Устанавливаем уровень обработки ошибок в системе
		/*if(KS_RELEASE==1)
			error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
		else
			error_reporting(E_ALL | E_STRICT);*/
	}

	/**
	 * Метод выполняет инициализацию полей класса
	 */
	private function Init()
	{
		$this->arIsModules=array();
		$this->arModules=array(
			'koloslib'=>array(
				'title'=>'koloslib',
				'check_db'=>'0',
			)
		);
		unset($arModules);
		include KS_MODULES_DIR.'/koloslib/cache/modules.php';
		if(isset($arModules) && is_array($arModules))
			$this->arModules=array_merge($this->arModules,$arModules);
		$this->bSave=false;
		$this->arFields=array('title','check_db');
		$this->arFieldsChecks=array(
			'title'=>'IsTextIdent',
			'check_db'=>'intval'
		);
		$this->arGetListResult=array();
		$this->arGetListSort=false;
	}

	/**
	 * Деструктор класса, пишет результат работы массива в кэш, если массив изменялся
	 */
	function __destruct()
	{
		if($this->bSave)
			SaveToFile(KS_MODULES_DIR.'/koloslib/cache/modules.php','$arModules',$this->arModules);
	}

	/**
	 * Метод выполняет сохранение записи о модуле в список модулей
	 */
	function Save($prefix='KS_',$data=false)
	{
		if(is_array($prefix))
		{
			$input=$prefix;
			$prefix='';
		}
		elseif ($data === false)
			$input = $_REQUEST;
		elseif(is_array($data))
			$input = $data;
		else
			throw new CKSError ("MAIN_INCORRECT_DATA_FORMAT", 999);
		$data = array();
		foreach ($this->arFieldsChecks as $key => $value)
			if(array_key_exists($prefix.$key,$input))
			{
				$sValue=call_user_func($value,$input[$prefix.$key]);
				if($sValue!==false)
					$data[$key]=$sValue;
			}
		if(array_key_exists('title',$data))
			if($arItem=$this->GetRecord(array('title'=>$data['title'])))
			{
				$arItem=array_merge($arItem,$data);
				$this->arModules[$data['title']]=$arItem;
				$this->bSave=true;
				return $arItem['title'];
			}
		$arItem=array(
			'title'=>'',
			'check_db'=>0
		);
		$arItem=array_merge($arItem,$data);
		$this->arModules[$arItem['title']]=$arItem;
		$this->bSave=true;
		return $arItem['title'];
	}

	/**
	 * Метод выполняет обновление записи о модуле в списке модулей
	 */
	function Update($id, $value, $bFiltered=false)
	{
		throw new CError('KOLOSLIB_METHOD_NOT_SUPPORTED');
	}
	
	function DeleteItems($arFilter)
	{
		throw new CError('KOLOSLIB_METHOD_NOT_SUPPORTED');
	}

	/**
	 * Метод выполняет поиск в списке модулей.
	 */
	function GetList($arOrder=false,$arFilter=false,$limit=false,$arSelect=false,$arGroupBy=false)
	{
		$this->arGetListResult=array();
		if(is_array($arFilter))
			array_walk($this->arModules,array($this,'_Search'),$arFilter);
		else
			$this->arGetListResult=$this->arModules;
		if(is_array($arOrder))
		{
			$this->arGetListSort=$arOrder;
			uasort($this->arGetListResult,array($this,'_Sort'));
		}
		if(is_array($limit))
			if(count($limit)==1)
			{
				if(count($this->arGetListResult)>$limit[0])
					$this->arGetListResult=array_slice($this->arGetListResult,0,$limit[0],true);
			}
			elseif(count($limit)==2)
			{
				if(count($this->arGetListResult)>$limit[1])
					$this->arGetListResult=array_slice($this->arGetListResult,$limit[0],$limit[1],true);
			}
		return $this->arGetListResult;
	}

	/**
	 * Метод обеспечивает поиск и заполнение массива результатов
	 */
	private function _Search(&$arItem,$sKey,$arFilter)
	{
		$bAdd=true;
		if(is_array($arFilter))
			foreach($arFilter as $key=>$value)
				if(array_key_exists($key,$arItem))
					if($arItem[$key]!=$value)
					{
						$bAdd=false;
						break;
					}
		if($bAdd)
			$this->arGetListResult[$sKey]=$arItem;
	}

	/**
	 * Метод выполняет сравнение двух элементов массива по массиву сортировки
	 */
	private function _Sort($a,$b)
	{
		foreach($this->arGetListSort as $key=>$dir)
		{
			if($a[$key]>$b[$key])
				if($dir=='asc')
					return 1;
				else
					return -1;
			elseif($a[$key]<$b[$key])
				if($dir=='asc')
					return -1;
				else
					return 1;
			else
				continue;
		}
		return 0;
	}

	/**
	 * Метод возвращает одну запись из списка модулей
	 */
	function GetRecord($arFilter)
	{
		return $this->GetList(false,$arFilter,1);
	}

	/**
	 * Метод добавляет таблицу описывающую часть БД в кэш
	 */
	function CacheDBStructure(array $arCache)
	{
		$sCacheFile=KS_KS_MODULES_DIR.'/koloslib/cache/db_structure.php';
		include $sCacheFile;
		if(!isset($arStructure))
			$arStructure=array();
		$arStructure=array_merge($arStructure,$arCache);
		if(count($arStructure) > 0){
			try{
				SaveToFile($sCacheFile,'$arStructure',$arStructure);
				return true;
			}catch(CKSError $e){
				return false;
			}
		}
		return false;
	}

	/**
	 * Метод выполняет перерасчет стркутуры базы данных хранящейся в конфигурации сайта на основании структур
	 * таблиц различных модулей, при этом происходит сохранение сгенерированной структуры.
	 */
	function RecountDBStructure()
	{
		if($arModules=$this->GetList(false,array('active'=>1)))
		{
			$arResultStructure=array();
			foreach($arModules as $arModule)
				if(file_exists(KS_MODULES_DIR.'/'.$arModule['title'].'/install/db/db_structure.php'))
				{
					include KS_MODULES_DIR.'/'.$arModule['title'].'/install/db/db_structure.php';
					$arResultStructure=array_merge($arResultStructure,$arStructure);
				}
			if(file_exists(KS_MODULES_DIR.'/koloslib/install/db/db_structure.php'))
			{
				include KS_MODULES_DIR.'/koloslib/install/db/db_structure.php';
				$arResultStructure=array_merge($arResultStructure,$arStructure);
			}
			SaveToFile(KS_MODULES_DIR.'/koloslib/cache/db_structure.php','$arStructure',$arResultStructure);
		}
	}

	/**
	 * Проверяет является ли указанный модуль, модулем использующим систему koloslib.
	 * Возвращает описание модуля
	 * @param $module - имя модуля (текстовый идентификатор).
	 */
	function IsModule($module)
	{
		if(!preg_match('#[\w\d]+#',$module)) return false;
		if(array_key_exists($module,$this->arIsModules))
			return $this->arIsModules[$module];
		elseif(array_key_exists($module,$this->arModules))
			$this->arIsModules[$module]=file_exists(KS_MODULES_DIR.'/'.$module);
		elseif($module=='main')
			$this->arIsModules[$module]=true;
		elseif($res=$this->GetRecord(array('title'=>$module)))
			$this->arIsModules=file_exists(KS_MODULES_DIR.'/'.$module);
		else
			$this->arIsModules[$module]=false;
		return $this->arModules[$module];
	}
}