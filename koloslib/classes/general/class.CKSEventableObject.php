<?php
/**
 * Класс реализует поддержку событий при изменении объекта
 * @author Dmitry Konev <d.konev@kolosstudio.ru>, BlaDe39 <blade39@kolosstudio.ru>
 * @since 25.01.2012
 * @version 1.0
*/

class CKSEventebleObject extends CKSBaseObject implements IKSListObject
{
	private $sEventPostfix;
	private $sEventModule;
	private $obObject;

	/**
	 * конструктор объекта, записывает в поле класса объект который требует добавления системы событий,
	 * также заполняет соответствующие поля значениями модуля и постфикса
	 * @param CKSObject - объект для работы с БД
	 * @param string - Постфикс события
	 * @param string - Модуль событий
	 */
	function __construct(CKSObject $obKSObject,$sEventPostfix=false,$sEventModule=false){
		$this->sEventPostfix = $sEventPostfix;
		$this->sEventModule = $sEventModule;
		$this->obObject=$obKSObject;
	}

	/**
	 * Метод выполняющий вызов обработчика через систему событий системы 1С-Битрикс
	 * @param array - Данные, которые надо передать обработчику
	 * @param stirng - Префикс события, которое надо вызвать
	 * @return void
	 */
	private function GenerateEvent($arEventData,$sEventPrefix)
	{
		if(!$sEventPrefix || $sEventPrefix=='')
			return false;
		$arEventData['object']=$this;
		$dbEvents=GetModuleEvents($this->sEventModule,$sEventPrefix.$this->sEventPostfix);
		while($arEvent=$dbEvents->Fetch()){
			ExecuteModuleEvent($arEvent,$arEventData);
		}
	}
	
	/**
	 * Обертка вокруг метода Save CKSObject
	 * @see CKSBaseObject
	 */
	function Save($prefix="KS_",$data=""){
		$arEventData=array(
			'data'=>$data
		);
		$this->GenerateEvent($arEventData,'onBeforeSave');
		$nId=$this->obObject->Save($prefix,$data);
		$arEventData=array(
			'save_result'=>$nId,
			'data'=>$data
		);
		$this->GenerateEvent($arEventData,'onAfterSave');
		return $nId;
	}

	/**
	 * Обертка вокруг метода Update CKSObject
	 * @see CKSObject
	 */
	function Update($id,$values,$bFiltered=false){
		$arEventData=array(
			'data'=>$values,
			'update_id'=>$id
		);
		$this->GenerateEvent($arEventData,'onBeforeUpdate');
		$nId=$this->obObject->Update($id,$values,$bFiltered);
		$arEventData=array(
			'update_result'=>$nId,
			'data'=>$this->obObject->GetRecord(array('id'=>$nId))
		);
		$this->GenerateEvent($arEventData,'onAfterDelete');
		return $nId;
	}

	/**
	 * Обертка вокруг метода DeleteItems CKSObject
	 * @see CKSObject
	 */
	function DeleteItems($arFilter)
	{
		$arEventData=array(
			'delete_filter'=>$arFilter
		);
		$this->GenerateEvent($arEventData,'onBeforeDelete');
		$arData=$this->obObject->GetList(false,$arFilter);
		if($arData && count($arData) > 0)
			$bDelRes=$this->obObject->DeleteItems($arFilter);
		$arEventData=array(
			'delete_result'=>$bDelRes,
			'delete_data'=>$arData
		);
		$this->GenerateEvent($arEventData,'onAfterDelete');
		return $bDelRes;
	}

	/**
	 * Обертка вокруг метода GetList CKSObject
	 * @see CKSObject
	 */
	function GetList($arOrder=false,$arFilter=false,$arLimit=false,$arSelect=false,$arGroup=false){
		return $this->obObject->GetList($arOrder,$arFilter,$arLimit,$arSelect,$arGroup);
	}

	/**
	 * Обертка вокруг метода GetRecord CKSObject
	 * @see CKSObject
	 */
	function GetRecord($arFilter){
		return $this->obObject->GetRecord($arFilter);
	}
}