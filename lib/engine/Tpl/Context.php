<?php

/**
 * Хранилище переменных для шаблона.
 * @author Mic, 2010
 */
class Tpl_Context
{
	/**
	 * Хранилище переменных.
	 * @var array
	 */
	private $_store = array();
	
	/**
	 * @return Tpl_Context
	 */
	public static function Factory()
	{
		return new Tpl_Context();
	}
	
	/**
	 * Возвращает все сохраненные переменные.
	 * @return array
	 */
	public function GetAll()
	{
		return $this->_store;
	}
	
	/**
	 * Сохраняет переменную.
	 * @param string $name
	 * @param mixed $value
	 * @return Tpl_Context
	 */
	public function Set($name, $value)
	{
		//Второй раз не назначаем:
		if (isset($this->_store[$name]))
		{
			throw new Exception_Tpl_AlreadyAssigned($name);
		}
		
		$this->_store[$name] = $value;
		
		return $this;
	}
};
