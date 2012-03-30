<?php
/**
 * Класс описывающий ошибку при работе с базой данных, наследует класс CKSError
 * @author BlaDe39 <blade39@kolosstudio.ru>, Dmitry Konev <d.konev@kolosstudio.ru>
 * @version 1.0
 * @since 25.01.2012
 */
class CKSDBError extends CError {
	private $sQuery;

	/**
	 * Конструктор класса, выполняет подмену стандартного конструктора, в зависимости от настроек
	 * модуля и системы 1С-Битрикс выполняет вывод ошибки или в лог (задаётся на настройках модуля) или на экран пользователя
	 * @param string - Сообщение об ошибке
	 * @param int - Код ошибки
	 * @param string - Текст запроса к БД
	*/
	function __construct($sMessage='',$nCode=0,$sQuery=''){
		parent::__construct($sMessage,intval($nCode));
		$this->sQuery=$sQuery;
 		if($this->sQuery){
			$sQuery = preg_replace("/([0-9a-f]){32}/", "********************************", $sQuery); // Hides all hashes
			$sQueryStr = "$sQuery";
		}
		$sErrorText="Ошибка вызова MySQL: ".$this->getMessage()."\n".
			"Код ошибки: ".$this->sError."\n".
			"Текст запроса: ".$sQueryStr."\n".
			"Стэк вызова функций: \n";
		$arTrace=$this->getTrace();
		foreach($arTrace as $item=>$arFunction){
			$sErrorText.='В файле: '.$arFunction['file']."\n";
			if(array_key_exists('class',$arFunction) && ($arFunction['class']!='')){
				$sErrorText.=$arFunction['class'].$arFunction['type'].$arFunction['function'].'() - строка '.$arFunction['line'];
			}else{
				$sErrorText.=$arFunction['function'].'() - строка '.$arFunction['line'];
			}
			$sErrorText.="\n".'----------------------------------------------------'."\n";
		}
		if(COption::GetOptionString('koloslib','db_log')=='Y'){
			error_log(date('d.m.Y H:i').' '.$sErrorText."\n===========================\n",3, ROOT_DIR.COption::GetOptionString('koloslib','db_log_path'));
		}
		$this->sMessage="DB_MYSQL_ERROR";
		$this->sErrorText='';
	}
}