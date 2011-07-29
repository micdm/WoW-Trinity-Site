<?php

/**
 * Класс персонажа ;).
 * @author Mic, 2010
 */
class User_Character_Class
{
	const DEATHKNIGHT								= 6;
	
	/**
	 * Номер класса.
	 * @var integer
	 */
	private $_class;
	
	/**
	 * 
	 * @param integer $class
	 */
	public function __construct($class)
	{
		$this->_class = $class;
	}
	
	public function __toString()
	{
		$classes = Env::Get()->config->Get('classes', 'game');
		return Util_String::ToLower($classes[$this->_class]);
	}
	
	/**
	 * Является ли персонаж рыцарем смерти?
	 * @return bool
	 */
	public function IsDeathknight()
	{
		return $this->_class == self::DEATHKNIGHT;
	}
};
