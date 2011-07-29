<?php

/**
 * Раса персонажа.
 * @author Mic, 2010
 */
class User_Character_Race
{
	const HUMAN										= 1;
	const ORC										= 2;
	
	/**
	 * Номер расы.
	 * @var integer
	 */
	private $_race;
	
	/**
	 * Возвращает список рас Альянса.
	 * @return array
	 */
	public static function GetAllianceFactions()
	{
		return Env::Get()->config->Get('alliance', 'game');
	}
	
	/**
	 * Возвращает список рас Альянса в виде строки через запятую.
	 * @return string
	 */
	public static function GetAllianceAsString()
	{
		return implode(',', self::GetAllianceFactions());
	}
	
	/**
	 * 
	 * @param integer $race
	 */
	public function __construct($race)
	{
		$this->_race = $race;
	}
	
	public function __toString()
	{
		$races = Env::Get()->config->Get('races', 'game');
		return Util_String::ToLower($races[$this->_race]);
	}
	
	/**
	 * Принадлежит ли раса Альянсу?
	 * @return bool
	 */
	public function IsAlliance()
	{
		return in_array($this->_race, self::GetAllianceFactions());
	}
};
