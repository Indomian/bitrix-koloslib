<?php
/**
 * Класс описывающий общую ошибку системы, наследует системный класс Exception
 * @version 1.0
 * @author BlaDe39 <blade39@kolosstudio.ru>, Dmitry Konev <d.konev@kolosstudio.ru>
 * @since 25.01.2012
 */
class CKSError extends Exception {
	protected $nError;
	protected $sErrorText;

	/**
	 * Конструктор класса, выполняет вызов родительского класса и заполняет свои поля
	 * @param string - Текст или языковая константа ошибки
	 * @param int - Код ошибки
	 * @param string - Альтернативный текст ошибки
	 * @return void
	 */
	function __construct($sMessage="",$nCode=0,$sText=''){
		parent::__construct($sMessage,intval($nCode));
		$this->nError=$nCode;
		$this->sErrorText=$sText;
	}

	/**
	 * Магический метод преобразующий объект в строку, используется при выводе ошибки в браузер пользователя
	 * @param void
	 * @return string
	 */
	function __toString()
	{
		$nCode=$this->getCode();
		$sText=$this->GetErrorText();
		if(KOLOSLIB_DEBUG==1){
			$arTrace=$this->getTrace();
			$sText.='<table border="1"><tr><td>#</td><td>File</td><td>Line</td><td>function</td></tr>';
			foreach($arTrace as $i=>$arRow){
				$sText.='<tr><td>'.$i.'</td><td>'.$arRow['file'].'</td><td>'.$arRow['line'].'</td><td>'.$arRow['function'].'</td></tr>';
			}
			$sText.='</table>';
		}
		return $sText;
	}

	/**
	 * Метод обёртка для функции 1С-Битрикс GetMessage(), позволяет получить текст ошибки на нормальном языке по её текстовому коду
	 * @param void
	 * @return string
	 */
	function GetErrorText()
	{
		$text=GetMessage($this->getMessage());
		if(!$text)
			$text=$this->getMessage();
		return $text;
	}

	/**
	 * Возвращает альтернативный текст ошибки
	 * @param void
	 * @return string
	 */
	function GetAdditionalText(){
		return $this->sErrorText;
	}
}