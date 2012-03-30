<?php
/**
 * Класс реализующий взаимодействие с БД через интерфейс системы 1С-Битрикс и преобразующий результаты запросов в ней в
 * стандартный массив
 * @author Dmitry Konev <d.konev@kolosstudio.ru>, BlaDe39 <blade39@kolosstudio.ru>
 * @since 25.01.2012
 * @version 1.0
 */
define('PREFIX','');

class CKSBitrixDatabase
{
	private $obDB;
	private $mysql_error = '';
	private $mysql_error_num = 0;
	private $query_id = false;

	/**
	 * В конструкторе инициализируется объект для работы с БД
	 * @param void
	 */
	function __construct()
	{
		global $DB;
		$this->obDB = $DB;
	}

	/**
	 * Выполняет запрос к БД
	 * @param string
	 * @return string
	 */
	function query($query)
	{
		if(defined('KOLOSLIB_DEBUG_QUERY') && KOLOSLIB_DEBUG_QUERY==1)
			echo $query."<br/>";
		if(!($this->query_id = $this->obDB->Query($query, false) ))
		{
			$this->mysql_error = mysql_error();
			$this->mysql_error_num = mysql_errno();
			throw new CDBError($this->mysql_error, $this->mysql_error_num, $query);
		}
		return $this->query_id;
	}

	/**
	 * Возвращает содержимое одно строки
	 * @param string
	 * @return array
	 */
	function get_row($query_id = '')
	{
		if ($query_id == '') $query_id = $this->query_id;
		return $query_id->Fetch();
	}

	/**
	 * Возвращает все строки, которые были найдены в результате запроса
	 * @param string
	 * @return array
	 */
	function get_array($query_id = '') {
		if ($query_id == '') $query_id = $this->query_id;

		$arResult = array();
		while( $arData = $query_id -> Fetch() )
			$arResult[] = $arData;

		return $arResult;
	}

	/**
	 * Количество найденых строк, согласно запросу
	 * @param string
	 * @return int
	 */
	function num_rows($query_id = '')
	{
		if ($query_id == '') $query_id = $this->query_id;
		return $query_id->SelectedRowsCount();
	}

	/**
	 * Возвращает номер последней вставленной записи. В качестве
	 * параметра можно передать код запроса.
	 * По умолчанию используется код последненго запроса.
	 * @param void
	 * @return int
	 */
	function insert_id()
	{
		return $this->obDB->LastID();
	}

	/**
	 * Метод возвращает количество строк затронутых при выполнении последней операции.
	 * @param void
	 * @return int
	 */
	function AffectedRows()
	{
		return $this->query_id->AffectedRowsCount();
	}

	/**
	 * Возвращает столбцы, которые были получены в результате запроса
	 * @param string
	 * @return array
	 */
	function get_result_fields($query_id = '')
	{
		if ($query_id == '') $query_id = $this->query_id;

		while ($field = @mysql_fetch_field($query_id))
		{
            $fields[] = $field;
		}
		return $fields;
   	}

	/**
	 * Экранирует данные, которые пойдут в запрос
	 * @param mixed
	 * @return mixed
	 */
	function safesql( $source )
	{
		if(ini_get('magic_quotes_gpc')==1)
		{
			$source=stripslashes($source);
		}

		return $this->obDB->ForSql($source);
	}

	/**
	 * Метод опрашивает таблицу, и получает список её полей. Копия метода из CKSDBController, т.к. часто требуется в работе CKSObject
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

	function begin()
	{
	}

	function commit()
	{
	}

	function rollback()
	{
	}
}