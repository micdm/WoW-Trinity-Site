<?php

/**
 * Один уровень адреса.
 * @package Routing
 * @author Mic, 2010
 */
class Routing_Section
{
	/**
	 * XML-описание секции.
	 * @var SimpleXMLElement
	 */
	private $_node;
	
	/**
	 * Часть URL, с которой сопоставляется секция.
	 * @var string
	 */
	private $_url;
	
	/**
	 * Массив найденных совпадений.
	 * @var array
	 */
	private $_matches;
	
	/**
	 * Родительская секция.
	 * @var Routing_Section
	 */
	private $_parent;
	
	/**
	 * Ассоциированный с секцией метод.
	 * @var callback
	 */
	private $_method;
	
	/**
	 * Аргументы.
	 * @var array
	 */
	private $_args;
	
	/**
	 * Ассоциированные с секцией параметры.
	 * @var array
	 */
	private $_params;
	
	/**
	 * Находит первую соответствующую текущему URL секцию.
	 * @return Routing_Section
	 */
	public static function Find()
	{
		$section = new self(new SimpleXMLElement(CONFIG_ROOT.'routes.xml', 0, true), Env::Get()->request->GetUrl());
		return $section->_FindFinalPart();
	}
	
	public function __construct(SimpleXMLElement $node, $url, Routing_Section $parent = null)
	{
		$this->_node = $node;
		$this->_url = $url;
		$this->_parent = $parent;
	}
	
	/**
	 * Ищет секцию, соответствующую маршруту.
	 * @return Routing_Section
	 */
	private function _FindFinalPart()
	{
		if ($this->_IsMatching())
		{
			return $this;
		}
		else if ($this->_IsPart())
		{
			$url = trim(str_replace((string)$this->_node['starts'], '', $this->_url), '/');
			foreach ($this->_node as $node)
			{
				//Обходим дочерние секции:
				$child = new Routing_Section($node, $url, $this);
				if ($section = $child->_FindFinalPart())
				{
					return $section;
				}
			}
		}
		
		return null;
	}
	
	/**
	 * Проверяет, является ли секция конечным пунктом в маршруте.
	 * @return boolean
	 */
	private function _IsMatching()
	{
		if ($this->_node['is'] !== null && (string)$this->_node['is'] == $this->_url)
		{
			//Кусок полностью совпадает.
			return true;
		}
		else if ($this->_node['matches'] !== null && preg_match('#'.(string)$this->_node['matches'].'#', $this->_url, $matches))
		{
			//Кусок совпадает по регулярному выражению.
			//Совпадения сохраняем для будущего использования.
			$this->_matches = array_slice($matches, 1);
			return true;
		}
		
		return false;
	}
	
	/**
	 * Проверяет, является ли секция одной из начальных частей маршрута.
	 * @return boolean
	 */
	private function _IsPart()
	{
		//Кусок либо пустой (для корневой секции), либо встречается в начале маршрута:
		return ($this->_node['starts'] !== null && ((string)$this->_node['starts'] == '' || strpos($this->_url, (string)$this->_node['starts']) === 0));
	}
	
	/**
	 * Возвращает метод, ассоциированный с данной секцией.
	 * @return callback
	 */
	public function GetMethod()
	{
		if ($this->_method === null)
		{
			if (empty($this->_node['call']))
			{
				throw new Exception_Routing_BadMethod('метод не определен');
			}
			
			//Разбираем метод:
			$method = explode('::', $this->_node['call']);
			if (count($method) == 1)
			{
				$method[] = 'Index';
			}
			else if (count($method) > 2)
			{
				throw new Exception_Routing_BadMethod('метод описан в неправильном формате');
			}
			
			$this->_method = $method;
		}
		
		return $this->_method;
	}
	
	/**
	 * Возвращает результаты поиска по регулярному выражению.
	 * @return array
	 */
	public function GetArgs()
	{
		if ($this->_args === null)
		{
			if ($this->_node['args'] !== null)
			{
				//Разбиваем названия ключей:
				$keys = explode(',', (string)$this->_node['args']);
				foreach ($this->_matches as $i => $match)
				{
					if (isset($keys[$i]) == false)
					{
						throw new Exception_Routing_BadArgs('совпадений больше, чем аргументов');
					}
					
					$result[$keys[$i]] = $match;
				}
				
				//Остальные ключи делаем пустыми:
				for ($j = $i + 1; $j < count($keys); $j += 1)
				{
					$result[$keys[$j]] = null;
				}
			}
			else if ($this->_matches !== null)
			{
				//Ключи не указаны, возвращаем обычный массив с ключами-числами:
				$result = $this->_matches;
			}
			else
			{
				$result = array();
			}
			
			$this->_args = $result;
		}
		
		return $this->_args;
	}
	
	/**
	 * Возвращает массив параметров, ассоциированных с данной секцией.
	 * @return array
	 */
	public function GetParams()
	{
		if ($this->_params === null)
		{
			$this->_params = (empty($this->_node['args']) ? array() : explode(',', $this->_node['args']));
		}
		
		return $this->_params;
	}
	
	/**
	 * Возвращает время жизни кэша для данного маршрута.
	 * @return integer
	 */
	public function GetCacheLifetime()
	{
		if ($this->_node['cache'] !== null)
		{
			//Время жизни укзано в самой секции:
			return intval($this->_node['cache']);
		}
		else if ($this->_parent !== null)
		{
			//Время жизни ищем у родителя:
			return $this->_parent->GetCacheLifetime();
		}

		//Время жизни не найдено в описании:
		throw new Exception_Routing_BadCacheLifetime();
	}
	
	/**
	 * Нужно ли для маршрута использовать серверный кэш?
	 * @return integer
	 */
	public function IsServerCacheNeeded()
	{
		if ($this->_node['server_cache'] !== null)
		{
			return intval($this->_node['server_cache']) == 1;
		}
		else if ($this->_parent !== null)
		{
			return $this->_parent->IsServerCacheNeeded();
		}

		throw new Exception_Routing_NoServerCacheFlag();
	}
}