<?php
/**
 * Класс обеспечивает классическое добавление уведомлений, их вывод и удаление, наследует от CKSBaseObject
 * @author Dmitry Konev <d.konev@kolosstudio.ru>, <BlaDe39@kolosstudio.ru>
 * @version 1.0
 * @since 25.01.2012
 */
class CKSLogs extends CKSBaseObject {
	protected $arNotifies;

	/**
	 * Конструктор класса, подгружает уведомления из сессии и обеспечивает их вывод
	 * @param void
	 */
	function __construct()
	{
		$this->arNotifies=(isset($_SESSION['notifies'])) ? $_SESSION['notifies'] : array();
	}
	
	/**
	 * Деструктор класса
	 */
	function __destruct()
	{
		//Выполняем обработку списка уведомлений, если
		//уведомление живет больше 3-х хитов - удаляем его.
		$_SESSION['notifies']=array();
		foreach($this->arNotifies as $arItem)
		{
			if(array_key_exists('life',$arItem))
			{
				$arItem['life']++;
			}
			else
			{
				$arItem['life']=1;
			}
			if($arItem['life']<NOTIFIES_LIFE)
			{
				$_SESSION['notifies'][]=$arItem;
			}
		}
	}
	
	/**
	 * Метод добавляет уведомление в массив уведомлений
	 * @param string - сообщение
	 * @param string - альтернативный текст 
	 * @param string - идентификатор сообщения (1-ошибка, 2-уведомление)
	 * @return int
	 */
	function AddNotify($msg,$text='',$type=NOTIFY_WARNING)
	{
		$this->arNotifies[]=array(
			'msg'=>$msg,
			'text'=>$text,
			'type'=>$type
		);
		return 1;
	}
	
	/**
	 * Метод выводит список уведомлений
	 * @param void
	 * @return void
	 */
	function GetNotifies()
	{
		return $this->arNotifies;
	}
	
	/**
	 * Метод возвращает уведомления и отчишает их список
	 * @param void
	 * @return array
	 */
	function ShowNotifies()
	{
		$arResult=$this->arNotifies;
		$this->arNotifies=array();
		return $arResult;
	}
}