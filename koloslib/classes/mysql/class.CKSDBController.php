<?php
/**
 * Класс является основным для всех наследуемых классов работы с базой данных
 * @author Dmitry Konev <d.konev@kolosstudio.ru>, BlaDe39 <blade39@kolosstudio.ru>
 * @version 1.0
 * @since 25.01.2012
 */

include_once KS_MODULES_DIR.'/koloslib/classes/mysql/class.CKSBitrixDatabase.php';

class CKSDBController extends CKSBitrixDatabase
{
	private $obDB;
	protected $arDBStructure;
	private $query_id = false;

	/**
	 * Метод возвращает список таблиц текущей базы данных
	 * @param bool - выбирать поля
	 * @return array
	 */
	public function ListTables($bGetFields=false)
	{
		//Получаем список таблиц
		$arDB=array();
		$rs=$this->query('SHOW TABLES');
		while ($table = $rs->Fetch())
			$arDB[]=current($table);
		if($bGetFields)
		{
			$arResult=array();
			foreach($arDB as $key=>$table)
			{
				$res=$this->query('DESCRIBE '.$table);
				if($this->num_rows($res)>0)
				{
					$arTable=array();
					while($arRow=$res->Fetch())
						$arTable[$arRow['Field']]=$arRow;
					$arResult[$table]=$arTable;
				}
			}
		}
		else
			$arResult=$arDB;
		return $arResult;
	}

	/**
	 * Метод опрашивает таблицу, и получает список её полей.
	 * @param $sTable таблица для которой надо получить поля
	 * @param $sPrefix префикс названия полей которые надо получить
	 * @return array
	 */
	public function GetTableFields($sTable,$sPrefix='')
	{
		$res=$this->query('DESCRIBE '.PREFIX.$sTable);
		if($this->num_rows($res)>0)
		{
			$arTable=array();
			while($arRow=$res->Fetch())
				if($sPrefix!='')
				{
					if(preg_match('#^'.$sPrefix.'#i',$arRow['Field']))
						$arTable[$arRow['Field']]=$arRow;
				}
				else
					$arTable[$arRow['Field']]=$arRow;
			return $arTable;
		}
		throw new CDBError('SYSTEM_TABLE_NOT_FOUND',1,$sTable);
	}

	/**
	 * Метод выполняет добавление таблицы в базу данных mysql
	 */
	function AddTable($sTable,$arTableStructure)
	{
		if(!is_array($arTableStructure)) return false;
		$sQuery='CREATE TABLE IF NOT EXISTS '.PREFIX.$sTable;
		$arFields=array();
		$arFullText=array();
		foreach($arTableStructure as $sField=>$arFieldParams)
		{
			//Значения по умолчанию
			if(!isset($arFieldParams['Extra'])) $arFieldParams['Extra']='';
			if(!isset($arFieldParams['Default'])) $arFieldParams['Default']='';
			if(!isset($arFieldParams['Null'])) $arFieldParams['Null']='NO';
			if(!isset($arFieldParams['Key'])) $arFieldParams['Key']='';
			if(!isset($arFieldParams['Type'])) $arFieldParams['Type']='char(255)';
			$sLine='`'.$sField.'` '.
				$arFieldParams['Type'].' '.
				($arFieldParams['Null']=='NO'?'NOT NULL':'NULL').' ';
			if($arFieldParams['Extra']!='auto_increment')
				$sLine.=($arFieldParams['Default']!=''?" DEFAULT '".$arFieldParams['Default']."' ":" DEFAULT ''");
			else
				$sLine.=$arFieldParams['Extra'];
			$sLine.=($arFieldParams['Key']=='PRI'?' PRIMARY KEY':'');
			$arFields[]=$sLine;
			if($arFieldParams['Extra']=='fulltext')
				$arFullText[]=$arFieldParams['Field'];
		}
		if(count($arFullText)>0)
			$arFields[]=' FULLTEXT INDEX ('.join(',',$arFullText).')';
		if(count($arFields)>0)
			$sQuery.='('.join(',',$arFields).') TYPE=MyISAM';
		try
		{
			$this->query($sQuery);
		}
		catch(CError $e)
		{
			throw new CError("DB_MYSQL_TABLE_CREATE_ERROR",$e->getCode(),$e->getMessage());
		}
	}

	/**
	 * Метод выполняет добавление колонки в таблицу
	 * Внимание метод изменен! В качестве параметра колнки допускается
	 * передавать только строку или массив в формате возвращаемом
	 * при анализе таблицы mysql
	 */
	public function AddColumn($sTable,$arColumn)
	{
		//Если передали число - выходим
		if(is_numeric($arColumn)) return false;
		//Если передали строку создаем поле по умолчанию
		if(!is_array($arColumn)&&is_string($arColumn))
		{
			$arColumn=array(
				'Field'=>$arColumn,
				'Type'	=> 	'char(255)',
			);
		}
		try
		{
			//Значения по умолчанию
			if(!isset($arColumn['Extra'])) $arColumn['Extra']='';
			if(!isset($arColumn['Default'])) $arColumn['Default']='';
			if(!isset($arColumn['Null'])) $arColumn['Null']='NO';
			if(!isset($arColumn['Key'])) $arColumn['Key']='';
			if(!isset($arColumn['Type'])) $arColumn['Type']='char(255)';
			$query="ALTER TABLE ".PREFIX.$sTable." ADD COLUMN `".
				$arColumn['Field'].'` '.
				$arColumn['Type'].' '.
				($arColumn['Null']=='NO'?'NOT NULL':'NULL').' ';
			if($arColumn['Extra']!='auto_increment')
				$query.=($arColumn['Default']!=''?" DEFAULT '".$arColumn['Default']."' ":" DEFAULT ''");
			else
				$query.=$arColumn['Extra'];
			$query.=($arColumn['Key']=='PRI'?' PRIMARY KEY':'');
			$this->query($query);
			if($arColumn['Extra']=='fulltext')
				$this->query('ALTER TABLE '.PREFIX.$sTable.' ADD FULLTEXT ('.$arColumn['Field'].')');
			if($arColumn['Key']=='UNI')
				$this->query('ALTER TABLE '.PREFIX.$sTable.' ADD UNIQUE ('.$arColumn['Field'].')');
		}
		catch (CError $e)
		{
			throw new CError("DB_MYSQL_COLUMN_CREATE_ERROR",$e->getCode(),$e->getMessage());
		}
		return true;
	}

	/**
	 * Метод выполняет обновление типа данных указанного поля
	 */
	public function UpdateColumnType($sTable,$sColumn,$sType)
	{
		//Если передали число - выходим
		if(is_numeric($sType)) return false;
		//Если передали строку создаем поле по умолчанию
		try
		{
			$arColumn=$this->DescribeColumn($sTable,$sColumn);
			if(!$arColumn) return false;
			if($arColumn['Type']==$sType) return false;
			if(strpos($sType,'int')!==false || strpos($sType,'float')!==false)
				$arColumn['Default']="0";
			if($sType=='text') $arColumn['Default']='';
			$query="ALTER TABLE ".PREFIX.$sTable." CHANGE COLUMN $sColumn $sColumn $sType ".($arColumn['Null']=='NO'?'NOT NULL':'NULL')." default '".$arColumn['Default']."'";
			$this->query($query);
		}
		catch (CError $e)
		{
			throw new CError("DB_MYSQL_COLUMN_TYPE_ERROR",$e->getCode(),$e->getMessage());
		}
		return true;
	}

	/**
	 * Метод выполняет обновление колонки указанной таблицы
	 */
	public function UpdateColumn($sTable,$sColumn,$arFieldParams)
	{
		//Если передали строку создаем поле по умолчанию
		try
		{
			//Значения по умолчанию
			if(!isset($arFieldParams['Extra'])) $arFieldParams['Extra']='';
			if(!isset($arFieldParams['Default'])) $arFieldParams['Default']='';
			if(!isset($arFieldParams['Null'])) $arFieldParams['Null']='';
			if(!isset($arFieldParams['Key'])) $arFieldParams['Key']='';
			if(!isset($arFieldParams['Type'])) $arFieldParams['Type']='char(255)';
			$arColumn=$this->DescribeColumn($sTable,$sColumn);
			if($arColumn['Key']=='UNI')
				//Есть ключ уникальности колонки
				if($arFieldParams['Key']!=$arColumn['Key'])
					//Ключи не совпадают, надо удалить ключ уникальности
					$this->query("ALTER TABLE ".PREFIX.$sTable." DROP INDEX ".$sColumn);

			$query="ALTER TABLE ".PREFIX.$sTable." CHANGE COLUMN `".$sColumn.'` `'.
				$arFieldParams['Field'].'` '.
				$arFieldParams['Type'].' '.
				($arFieldParams['Null']=='NO'?'NOT NULL':'NULL').' ';
			if($arFieldParams['Extra']!='auto_increment')
				$query.=($arFieldParams['Default']!=''?" DEFAULT '".$arFieldParams['Default']."' ":" DEFAULT ''");
			else
				$query.=$arFieldParams['Extra'];
			$this->query($query);
			if($arFieldParams['Extra']=='fulltext')
				$this->query('ALTER TABLE '.PREFIX.$sTable.' ADD FULLTEXT ('.$arFieldParams['Field'].')');
			if($arFieldParams['Key']=='UNI')
				$this->query('ALTER TABLE '.PREFIX.$sTable.' ADD UNIQUE ('.$arFieldParams['Field'].')');
		}
		catch (CError $e)
		{
			throw new CError("DB_MYSQL_COLUMN_UPDATE_ERROR",$e->getCode(),$e->getMessage());
		}
		return true;
	}

	/**
	 * Метод выполняет получение информации о колонке таблицы
	 * @param string - таблица
	 * @param string - колонка
	 * @return bool
	 */
	protected function DescribeColumn($sTable,$sColumn)
	{
		if(!$sTable) return false;
		/* Чтение всех полей таблицы */
		$query="SHOW COLUMNS FROM " . PREFIX . $sTable." WHERE Field='$sColumn'";
		$this->query($query);
		/* Формирование массива с параметрами полей - Type (тип данных), Size (размер в байтах) */
		while ($field = $this->get_row())
			return $field;
		return false;
	}

	/**
	 * Метод выполняет удаление таблицы или таблиц переданных методу
	 * @param $arTables mixed - список или одна таблица которые требуется удалить
	 * @return void
	 */
	public function DeleteTables($arTables)
	{
		if(!is_array($arTables)) $arTables=array($arTables);
		$arDBTables=$this->ListTables();
		$arTablesToDelete=array();
		foreach($arTables as $sTable)
			if(in_array(PREFIX.$sTable,$arDBTables) && IsTextIdent($sTable)) $arTablesToDelete[]=PREFIX.$sTable;
		if(count($arTablesToDelete)>0)
		{
			$query="DROP TABLE IF EXISTS ".join(',',$arTablesToDelete);
			$this->query($query);
		}
	}

	/**
	 * Метод выполняет удаление колонки из таблицы
	 * @param $sTable
	 * @param $sColumn
	 * @return bool
	 */
	public function DeleteColumn($sTable,$sColumn)
	{
		//Если передали строку создаем поле по умолчанию
		try
		{
			$query="ALTER TABLE ".PREFIX.$sTable." DROP COLUMN ".$sColumn;
			$this->query($query);
		}
		catch (CError $e)
		{
			throw new CError("DB_MYSQL_COLUMN_DROP_ERROR",$e->getCode(),$e->getMessage());
		}
		return true;
	}

	/**
	 * Метод выполняет переименование одной таблицы в другую
	 * @param $sTable
	 * @param $sNewName
	 * @return true
	 */
	public function RenameTable($sTable,$sNewName)
	{
		//Если передали строку создаем поле по умолчанию
		try
		{
			$query="ALTER TABLE ".PREFIX.$sTable." RENAME TO ".$sNewName;
			$this->query($query);
		}
		catch (CError $e)
		{
			throw new CError("DB_MYSQL_TABLE_RENAME_ERROR",$e->getCode(),$e->getMessage());
		}
		return true;
	}

	/**
	 * Метод осуществляет анализ таблицы
	 * @param string - таблица
	 * @param array - описание структуры
	 * @return void
	 */
	function CheckTable($sTable,$arTableStructure)
	{
		if(!is_array($this->arDBStructure))
			$this->arDBStructure=$this->ListTables(true);
		if(!array_key_exists(PREFIX.$sTable,$this->arDBStructure))
			throw new CDBError('TABLE_NOT_FOUND');
		else
			foreach($arTableStructure as $sField=>$arField)
			{
				if(array_key_exists($sField,$this->arDBStructure[PREFIX.$sTable]))
				{
					//Если поле таблице существует, надо проверить его параметры
					$bUpdate=false;
					foreach($arField as $sParam=>$sValue)
					{
						if($sParam=='Default' && $arField['Key']=='PRI')
							continue;
						if($sParam=='Extra' && $sValue=='fulltext')
						{
							if($this->arDBStructure[PREFIX.$sTable][$sField]['Key']=='MUL')
								continue;
							else
							{
								$bUpdate=true;
								break;
							}
						}
						if($this->arDBStructure[PREFIX.$sTable][$sField][$sParam]!=$sValue)
						{
							$bUpdate=true;
							break;
						}
					}
					if($bUpdate)
						$this->UpdateColumn($sTable,$sField,$arField);
				}
				else
					//Поле таблицы не существует, надо создать
					$this->AddColumn($sTable,$arField);
			}
	}

	/**
	 * Метод выполняющий анализ и сравнение структуры базы данных
	 * со структурой переданной в виде ассоциативного массива
	 * в качестве параметра
	 * @param array
	 * @return void
	 */
	function CheckDB($arDBStructure)
	{
		if(!is_array($arDBStructure) || count($arDBStructure)==0) return false;
		$this->arDBStructure=$this->ListTables(true);
		foreach($arDBStructure as $sTableName=>$arTableStructure)
			if(array_key_exists(PREFIX.$sTableName,$this->arDBStructure))
				$this->CheckTable($sTableName,$arTableStructure);
			else
				$this->AddTable($sTableName,$arTableStructure);
	}
}