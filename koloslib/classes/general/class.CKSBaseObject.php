<?php
abstract class CKSBaseObject {
	public function callStack(){
		try{
			throw new Exception();
		}catch (Exception $e){
			$call = $e->getTrace();
			$error = array();
			foreach($call as $index => $arr){
				$error[$index]['file'] = $arr['file'].'('.$arr['line'].')';
				$error[$index]['function'] = $arr['class'].$arr['type'].$arr['function'];
				$error[$index]['param'] = $arr['args'];
			}
			unset($error[0]);
			$error = array_values($error);
			var_dump($error);
			die;
		}
	}
}