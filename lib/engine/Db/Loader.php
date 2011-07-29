<?php

/**
 * Загрузчик данных из БД.
 * @author Mic, 2010
 */
class Db_Loader implements Loader_Interface
{
	/**
	 * Название подключения к БД.
	 * @var string
	 */
	private $_connect;
	
	/**
	 * SQL-запрос, который загрузит данные.
	 * @var string
	 */
	private $_sql;
	
	/**
	 * Метод, который будет использоваться для получения данных.
	 * @var string
	 */
	private $_method;
	
	/**
	 * Дополнительные параметры для запроса.
	 * @var array
	 */
	private $_params;
	
	/**
	 * @param $connect
	 * @param $sql
	 * @param $method
	 * @param $params
	 * @return Db_Loader
	 */
	public static function Factory($connect, $sql, $method, $params = null)
	{
		return new Db_Loader($connect, $sql, $method, $params);
	}
	
	public function __construct($connect, $sql, $method, $params = null)
	{
		$this->_connect = $connect;
		$this->_sql = $sql;
		$this->_method = $method;
		$this->_params = $params;
	}
	
	public function Load()
	{
		$result = Env::Get()->db->Get($this->_connect)->Query($this->_sql, $this->_params);
		return call_user_func(array($result, $this->_method));
	}
	
	public function Save($data)
	{
		
	}
};
