<?php
/**
 * Класс обеспечивает кэширование данных
 * @author BlaDe39 <blade39@kolosstudio.ru>, Dmitry Konev <d.konev@kolosstudio.ru>
 * @version 1.0
 * @since 25.01.2012
 */
class CKSPHPCache {
	protected $cacheTime;
	protected $cacheId;
	protected $isAlive;
	protected $isLock;
	protected $sCacheFile;
	protected $sCacheLock;

	/**
	 * Конструктор инициализирует кэш по заданным параметрам и возвращает объект кэша
	 * @param mixed - идентификатор кэша
	 * @param int - время кэширования в секундах
	 */
	function __construct($cacheId,$cacheTime)
	{
		$this->module=$module;
		$this->cacheTime=$cacheTime;
		$this->cacheId=$cacheId;
		$this->sCacheFile=KS_CACHE_PATH.'/'.$cacheId.'.php';
		$this->sCacheLock=KS_CACHE_PATH.'/'.$cacheId.'.lock';
		$this->isAlive=false;
		$this->isLock=false;
		if(defined('KS_SKIP_CACHE') && KS_SKIP_CACHE=='Y')
			$this->isAlive=false;
		else
			if(file_exists($this->sCacheFile))
				if((filemtime($this->sCacheFile)+$cacheTime)<time())
					if(file_exists($this->sCacheLock))
						$this->isAlive=true;
					else
					{
						if(!file_exists(KS_CACHE_PATH))
							if(!@mkdir(KS_CACHE_PATH,0755,true))
								throw new CError("SYSTEM_CACHE_WRITE_ERROR",1);
						@file_put_contents($this->sCacheLock,'');
						$this->isAlive=false;
						$this->isLock=true;
					}
				else
					$this->isAlive=true;
			else
			{
				if(!file_exists(KS_CACHE_PATH))
					if(!@mkdir(KS_CACHE_PATH,0755,true))
						throw new CError("SYSTEM_CACHE_WRITE_ERROR",1);
				@file_put_contents($this->sCacheLock,'');
				$this->isLock=true;
				$this->isAlive=false;
			}
	}

	/**
	 * Метод возвращает true если кэш еще не истек и false если истек (или не существует)
	 * @param void
	 * @return bool
	 */
	function Alive()
	{
		return $this->isAlive;
	}

	/**
	 * Метод разблокирует изменение кэша и удаляет файл кэша, если в процессе его создания произошла ошибка
	 * @param void
	 * @return void
	 */
	function Unlock()
	{
		if($this->isLock)
		{
			@unlink($this->sCacheLock);
			@unlink($this->sCacheFile);
		}
	}

	/**
	 * Метод выполняет возвращение данных из кэша
	 * @param void
	 * @return array|bool
	 */
	function GetData()
	{
		if(file_exists($this->sCacheFile))
		{
			$arData=array();
			include $this->sCacheFile;
			return $data;
		}
		return false;
	}

	/**
	 * Метод формирует выходную строку для переменной кэша
	 *
	 * @param string $var
	 * @param mixed $value
	 * @param int $tabs_count
	 * @return string
	 */
	private function OutputVar($var, $value, $tabs_count = 0)
	{
		$tabs = "";
		$tabs_count = intval($tabs_count);
		if ($tabs_count > 0)
			$tabs = str_repeat("\t", $tabs_count);

		if (!is_array($value))
			return $tabs . "'" . $var . "' => \"" . $value . "\"";

		$output = $tabs . "'" . $var . "' => array\n";
		$output .= $tabs . "(\n";
		if (count($value) > 0)
		{
			$var_number = 0;
			foreach ($value as $array_var => $array_value)
			{
				$var_number++;
				$output .= $this->OutputVar($array_var, $array_value, $tabs_count + 1);
				if ($var_number < count($value))
					$output .= ",";
				$output .= "\n";
			}
		}
		$output .= $tabs . ")";
		return $output;
	}

	/**
	 * Метод выполняет сохранение данных в кэш
	 * @param string - Данные для сохранения
	 * @return void
	 */
	function SaveToCache($data)
	{
		$this->data=$data;
		$result = "<?php\n\n";

		$result .= "/**\n";
		$result .= " * Кэш файл модуля \"" . $this->module . "\"\n";
		$result .= " * Последнее изменение: " . date("d.m.Y, H:i:s", time()) . "\n";
		$result .= " * Истечет: ".date("d.m.Y, H:i:s", time()+$this->cacheTime) . "\n";
		$result .= " * Ключ кэша: ".$this->cacheId."\n";
		$result .= " */\n\n";

		/* Запись конфигурационного массива */
		$var_number = 0;
		$result .= "\$data = array\n";
		$result .= "(\n";
  		foreach ($data as $key => $value)
  		{
  			$var_number++;
	  		$result .= $this->OutputVar($key, $value, 1);
	  		if ($var_number < count($this->data))
  				$result .= ",";
  			$result .= "\n";
	  	}
	  	$result .= ");\n";
		$result .= "\n?>";
   		$size = @file_put_contents($this->sCacheFile, $result);
   		if ($size == 0)
   			throw new CError("SYSTEM_CACHE_WRITE_ERROR",0);
		if(file_exists($this->sCacheLock)) @unlink($this->sCacheLock);
	}
}