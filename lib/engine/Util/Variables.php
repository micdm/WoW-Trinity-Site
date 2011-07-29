<?php

/**
 * Загрузчик переменных, которые хранятся в БД.
 * @author Mic, 2010
 */
class Util_Variables
{
	/**
	 * Возвращает значение переменной.
	 * @param string $name
	 * @return string
	 */
	public static function Get($name)
	{
		return Env::Get()->db->Get('game')->Query('
			SELECT value
			FROM #site.site_variables
			WHERE name = :name
		', array(
			'name' => array('s', $name)
		))->FetchOne();
	}
	
	/**
	 * Устанавливает значение переменной.
	 * @param string $name
	 * @param string $value
	 */
	public static function Set($name, $value)
	{
		Env::Get()->db->Get('game')->Query('
			REPLACE INTO #site.site_variables (name, value)
			VALUES (:name, :value)
		', array(
			'name' => array('s', $name),
			'value' => array('s', $value),
		));
	}
}