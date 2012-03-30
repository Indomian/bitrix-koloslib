<?php
interface IKSListObject {
	function Save($prefix='KS_',$data=false);
	function Update($id, $value, $bFiltered=false);
	function DeleteItems($arFilter);
	function GetList($arOrder=false,$arFilter=false,$limit=false,$arSelect=false,$arGroupBy=false);
	function GetRecord($arFilter);
}