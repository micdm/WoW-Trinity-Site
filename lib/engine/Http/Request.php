<?php

/**
 * Хранилище информации о HTTP-запросе.
 * @package Http
 * @author Mic, 2010
 */
class Http_Request
{
	/**
	 * Запрашиваемый адрес.
	 * @var string
	 */
	private $_url = '';
	
	/**
	 * Массив GET-переменных.
	 * @var array
	 */
	private $_get = array();
	
	/**
	 * Массив POST-переменных.
	 * @var array
	 */
	private $_post = array();
	
	public function __construct()
	{
		$this->_PrepareUrl();
		$this->_PrepareGetVars();
		$this->_PreparePostVars();
	}
	
	/**
	 * Вычисляет URL запроса.
	 */
	private function _PrepareUrl()
	{
		if (isset($_SERVER['REQUEST_URI']))
		{
			$chunks = explode('?', $_SERVER['REQUEST_URI'], 2);
			$this->_url = str_replace(SITE_RELATIVE_ROOT, '', $chunks[0]);
		}
	}
	
	/**
	 * Заполняет GET-переменные.
	 */
	private function _PrepareGetVars()
	{
		if (isset($_SERVER['REQUEST_URI']))
		{
			$chunks = explode('?', $_SERVER['REQUEST_URI'], 2);
			if (isset($chunks[1]))
			{
				parse_str($chunks[1], $this->_get);
			}
		}
	}
	
	/**
	 * Рекурсивно удаляет слэши в элементах массива.
	 * @param array $array
	 */
	private function _StripSlashesFromArray($array)
	{
		foreach ($array as &$item)
		{
			$item = is_array($item) ? $this->_StripSlashesFromArray($item) : stripslashes($item);
		}
		
		return $array;
	}
	
	/**
	 * Заполняет POST-переменные.
	 */
	private function _PreparePostVars()
	{
		//Если включено автоматическое экранирование, убираем слэши:
		$this->_post = get_magic_quotes_gpc() ? $this->_StripSlashesFromArray($_POST) : $_POST;
	}
	
	/**
	 * Возвращает URL запроса.
	 * @return string
	 */
	public function GetUrl()
	{
		return $this->_url;
	}	
	
	/**
	 * Возвращает URL запроса + относительный путь до корня.
	 * @param string $url
	 * @param bool $needHost
	 * @return string
	 */
	public function GetAbsoluteUrl($url, $needHost = false)
	{
		$result = Util_String::CombineSlashes('/'.SITE_RELATIVE_ROOT.$url);
		if ($needHost && isset($_SERVER['HTTP_HOST']))
		{
			$result = 'http://'.$_SERVER['HTTP_HOST'].$result;
		}
		
		return $result;
	}
	
	/**
	 * Возвращает адрес запроса с GET-параметрами.
	 */
	public function GetUri()
	{
		$query = $this->Get() ? '?'.http_build_query($this->Get()) : '';
		return $this->GetUrl().$query;
	}
	
	/**
	 * Возвращает значение GET-переменной.
	 * @param string $name
	 * @return mixed
	 */
	public function Get($name = null)
	{
		if (empty($name))
		{
			return $this->_get;
		}
		else
		{
			return isset($this->_get[$name]) ? $this->_get[$name] : null;
		}
	}

	/**
	 * Возвращает значение POST-переменной.
	 * @param string $name
	 * @return mixed
	 */
	public function Post($name = null)
	{
		if (empty($name))
		{
			return $this->_post;
		}
		else
		{
			return isset($this->_post[$name]) ? $this->_post[$name] : null;
		}
	}
	
	/**
	 * Обрабатывает один сложный элемент (массив). 
	 * @param array $item
	 * @return array
	 */
	private function _ParseInputItem($item)
	{
		$result = array();
		
		foreach ($item as $key => $value)
		{
			if (preg_match('#^[a-zA-Z_][a-zA-Z0-9_]*$#', $key))
			{
				$result[$key] = is_array($value) ? $this->_ParseInputItem($value) : htmlspecialchars($value);
			}
		}
		
		return $result;
	}
	
	/**
	 * Возвращает введенные пользователем данные, подготовленные к печати.
	 * @return array
	 */
	public function GetInput()
	{
		$get = $this->_ParseInputItem($this->Get());
		$post = $this->_ParseInputItem($this->Post());
		
		//У POST-переменных приоритет:
		return array_merge($get, $post);
	}
	
	/**
	 * Возвращает IP-адрес клиента.
	 * @return string
	 */
	public function GetIp()
	{
		return (empty($_SERVER['REMOTE_ADDR']) ? '0.0.0.0' : $_SERVER['REMOTE_ADDR']);
	}
};
