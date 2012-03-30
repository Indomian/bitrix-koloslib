<?php
/**
 * Класс обеспечивает взаимодействие с другими серверами посредством протокола http, наследует от класса CKSBaseObject
 * @author BlaDe39 <blade39@kolosstudio.ru>
 * @version 1.0
 * @since 25.01.2012
 */
class CKSHTTPInterface extends CKSBaseObject {
	protected $sUrl;
	protected $sDomain;
	protected $sPath;
	protected $sMode;
	protected $arData;
	protected $iPort;
	protected $arHeaders;
	protected $sBody;
	protected $sContentType;

	/**
	 * Конструктор класса, пока ничего не делает
	 * @param string - адрес, на который будем слать запросы
	 * @param string - протокол передачи данных (GET или POST)
	 * @param string - порт
	 */
	function __construct($url,$mode='GET',$port='80')
	{
		$this->sUrl=$url;
		$this->sMode=$mode;
		$this->arData=array();
		$this->iPort=$port;
		$this->arHeaders=array();
		$this->ParseUrl($url);
		$this->sBody='';
		$this->sContentType='';
	}

	/**
	 * Метод разбивает строку url на домен, строку запроса и GET параметры
	 * @param string - адрес
	 * @return void
	 */
	function ParseUrl($url)
	{
		if(preg_match('#^(http://)?([a-z0-9\.]+)(\:([0-9]+))?(/[^?]+)?(\?(.*))?$#i',$url,$matches))
		{

			$this->sDomain=$matches[2];
			$this->sPath=$matches[5]!=''?$matches[5]:'/';
			if($matches[4]!='') $this->iPort=intval($matches[4]);
			$this->arData=array_merge($this->arData,$this->ParseGetString($matches[7]));
		}
		else
		{
			$this->sPath='/';
			$this->sDomain='';
		}
	}

	/**
	 * Метод устанавливает значение заголовка для запроса
	 * @param string - заголовок
	 * @param string - значение заголовка
	 * @return void
	 */
	function SetHeader($name,$value)
	{
		$this->arHeaders[$name]=$value;
	}

	/**
	 * Метод разбивает строку с GET параметрами
	 * @param string - адрес
	 */
	function ParseGetString($get)
	{
		$arResult=array();
		$arParams=explode('&',$get);
		foreach($arParams as $sParam)
		{
			$arParam=explode('=',$sParam);
			$arResult[urldecode($arParam[0])]=urldecode($arParam[1]);
		}
		return $arResult;
	}

	/**
	 * Метод устанавливает данные которые должны быть отправлены в запросе
	 * @param array||string - массив с данными или ключ. Если указан ключ, то второй параметр должен содержать значение,
		которое будет ассоциировано с этим ключом
	 * @param string - значение
	 * @return void
	 */
	function SetData($arData,$value='')
	{
		if(is_array($arData))
		{
			$this->arData=array_merge($this->arData,$arData);
		}
		elseif(is_string($arData))
		{
			$this->arData[$arData]=$value;
		}
		if($this->sMode=='POST')
		{
			$this->sBody=$this->EncodePostData($this->arData);
			$this->sContentType='application/x-www-form-urlencoded';
		}
	}

	/**
	 * Метод устанавливает содержимое тела запроса в raw формате
	 * @param string - тело запроса
	 * @return void
	 */
	function SetRawBody($sBody)
	{
		$this->sBody=$sBody;
	}

	/**
	 * Метод устанавливает тип контента
	 * @param string
	 * @return void
	 */
	function SetContentType($sType)
	{
		$this->sContentType=$sType;
	}

	/**
	 * Метод отправляет запрос на сервер
	 * @param void
	 * @return array - массив, в котором содержатся данные ответа от сервера и заголовки
	 */
	function Send()
	{
		if($this->sDomain=='') return false;
		$sock=fsockopen($this->sDomain,$this->iPort,$errorCode,$errorText,10);
		if($sock)
		{
			if($this->sMode=='GET')
			{
				$header = "GET ".$this->sPath."?".$this->EncodePostData($this->arData)." HTTP/1.0\r\n";
    			$header .= "Host: ".$this->sDomain."\r\n";
    			if(count($this->arHeaders)>0)
    			{
    				foreach($this->arHeaders as $key=>$value)
    				{
    					$header.=$key.':'.$value."\r\n";
    				}
    			}
	    		$header .= "Connection: Close\r\n\r\n";
	    		$body='';
			}
			elseif($this->sMode=='POST')
			{
				$header = "POST ".$this->sPath." HTTP/1.0\r\n";
    			$header .= "Host: ".$this->sDomain."\r\n";
    			if(count($this->arHeaders)>0)
    			{
    				foreach($this->arHeaders as $key=>$value)
    				{
    					$header.=$key.':'.$value."\r\n";
    				}
    			}
				$header .= "Content-Length: ".strlen($this->sBody)."\r\n";
				if($this->sContentType!='')
					$header .= "Content-Type: ".$this->sContentType."\r\n";
	    		$header .= "Connection: Close\r\n\r\n";
			}
// 			echo $header.$this->sBody;
			fwrite($sock,$header.$this->sBody."\r\n");
			$result='';
			while (!feof($sock))
			{
        		$result.=fgets($sock, 1024);
    		}
//     		echo $result;
	    	if(strlen($result)>0)
	    	{
	    		$arData=$this->GetHeader($result);
	    	}
	    	fclose($sock);
	    	return $arData;
		}
		else
		{
			throw new CHTTPError('SYSTEM_HTTP_SOCKET_ERROR',$errorCode,$errorText);
		}
	}

	/**
	 * Метод выполняет скачивание файла с сервера
	 * @param string - куда сохранять скачанный файла
	 * @param int - с какого байта начинать
	 * @param int - максимальное время для скачивания
	 * @param int - максимальный размер файла
	 * @return int||bool
	 */
	function Download($to,$from=0,$timeout=30,$maxSize=5)
	{
		$begin=time();
		$maxSize*=1024*1024;
		if($this->sDomain=='') return false;
		$sock=fsockopen($this->sDomain,$this->iPort,$errorCode,$errorText,5);
		if($sock)
		{
			$header = "GET ".$this->sPath." HTTP/1.1\r\n";
    		$header .= "Host: ".$this->sDomain."\r\n";
    		if($from>0)
    		{
    			$header.='Range: bytes='.$from."-\r\n";
    		}
	    	$header .= "Connection: Close\r\n\r\n";
	    	$body='';
			fwrite($sock,$header.$body."\r\n");
			$result='';
			while (!feof($sock))
			{
        		$result.=fread($sock, 1024);
        		if(($begin+$timeout)<time()||strlen($result)>$maxSize)
        			break;
    		}
    		fclose($sock);
	    	if(strlen($result)>0)
	    	{
	    		$arData=$this->GetHeader($result);
	    		if(strlen($arData['body'])>0)
	    		{
	    			$file=fopen($to,'a+b');
	    			fwrite($file,$arData['body']);
	    			fclose($file);
	    			return filesize($to);
	    		}
	    	}
	    	return false;
		}
		else
		{
			throw new CHTTPError('SYSTEM_HTTP_SOCKET_ERROR',$errorCode,$errorText);
		}
	}

	/**
	 * Метод кодирует массив в формат x-www-form-encode
	 * @param array
	 * @return int
	 */
	function EncodePostData($data)
	{
		if(is_array($data))
		{
			$arResult=array();
			foreach($data as $key=>$value)
			{
				if($key!='')
				{
					$arResult[]=urlencode($key).'='.urlencode($value);
				}
			}
			if(is_array($arResult))
			{
				return join('&',$arResult);
			}
			return false;
		}
		return false;
	}

	/**
	 * Метод возвращает данные заголовков
	 * @param string - ответ от сервера
	 * @return array||bool - массив с заголовками
	 */
	function GetHeader($sdata)
	{
		$arResult=array();
		if(strlen($sdata)>0)
		{
			if(strpos($sdata,"\r\n\r\n")>0)
			{
				$arItems=explode("\r\n\r\n",$sdata);
				$arHeaders=explode("\r\n",array_shift($arItems));
				$arResult['body']=join("\r\n\r\n",$arItems);
				foreach($arHeaders as $arRow)
				{
					$arRow=explode(":",$arRow);
					if(preg_match('#^(HTTP/1\.(0|1))? +([0-9]{3,3})(.*)#i',$arRow[0],$matches))
					{
						$arResult['headers']['RESULT']=array(
							'code'=>$matches[3],
							'answer'=>$matches[4],
						);
					}
					if($arRow[0]!='')
						$arResult['headers'][array_shift($arRow)]=trim(join(':',$arRow));
				}
				return $arResult;
			}
		}
		return false;
	}
}