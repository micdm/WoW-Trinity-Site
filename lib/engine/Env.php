<?php

/**
 * Хранилище разных переменных. Для уменьшения связности :).
 * @author Mic, 2010
 */
class Env
{
	/**
	 * Для синглетона.
	 * @var Env
	 */
	private static $_instance;
	
	/**
	 * Список свойств.
	 * @var array
	 */
	private $_properies;
	
	/**
	 * 
	 * @var Cache_Manager
	 */
	public $cache;

	/**
	 * 
	 * @var Config
	 */
	public $config;
	
	/**
	 * 
	 * @var Db_Manager
	 */
	public $db;
	
	/**
	 * 
	 * @var Dev_Debug_Manager
	 */
	public $debug;
	
	/**
	 * 
	 * @var Third_Forum_Base
	 */
	public $forum;
	
	/**
	 * 
	 * @var Dev_Logger
	 */
	public $log;
	
	/**
	 * 
	 * @var Http_Response
	 */
	public $response;
	
	/**
	 * 
	 * @var Http_Request
	 */
	public $request;
	
	/**
	 * 
	 * @var User_Session
	 */
	public $session;
	
	/**
	 * 
	 * @var User_Manager
	 */
	public $user;
	
	/**
	 * Описывает новое свойство, которое может быть потребовано.
	 * @param $name
	 * @param $class
	 * @param $method
	 */
	private function _AddProperty($name, $class, $method = null)
	{
		$property = array(
			'class' => $class,
			'method' => $method
		);

		$this->_properies[$name] = $property;
	}
	
	/**
	 * Удаляет переменные, чтобы работала подсветка в Eclipse и одновременно __get() ;).
	 */
	private function _UnsetProperties()
	{
		foreach ($this->_properies as $name => $property)
		{
			unset($this->$name);
		}
	}

	private function _Init()
	{
		//Добавляем свойства:
		$this->_AddProperty('cache', 'Cache_Manager');
		$this->_AddProperty('config', 'Config');
		$this->_AddProperty('db', 'Db_Manager');
		$this->_AddProperty('debug', 'Dev_Debug_Manager');
		$this->_AddProperty('forum', 'Third_Forum_Base', 'Factory');
		$this->_AddProperty('log', 'Dev_Logger');
		$this->_AddProperty('response', 'Http_Response');
		$this->_AddProperty('request', 'Http_Request');
		$this->_AddProperty('session', 'User_Session');
		$this->_AddProperty('user', 'User_Manager');
		
		//Удаляем объявленные, чтобы работал __get:
		$this->_UnsetProperties();
	}
	
	/**
	 * Стандартный геттер: свойство определено - возвращаем, нет - создаем и возвращаем.
	 * @param string $name
	 */
	public function __get($name)
	{
		if (empty($this->$name))
		{
			//Свойство не описано:
			if (empty($this->_properies[$name]))
			{
				throw new Exception_Runtime('свойство отсутствует');
			}
			
			//В зависимости от свойства создаем либо объект класса, либо вызываем статический метод:
			$property = $this->_properies[$name];
			$this->$name = empty($property['method']) ? new $property['class']() : call_user_func(array($property['class'], $property['method']));
		}
		
		return $this->$name;
	}
	
	/**
	 * Синглетон.
	 * @return Env
	 */
	public static function Get()
	{
		if (empty(self::$_instance))
		{
			self::$_instance = new self();
			self::$_instance->_Init();
		}
		
		return self::$_instance;
	}
};
