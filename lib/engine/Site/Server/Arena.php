<?php

/**
 * Загрузчик информации про арену.
 * @package Site_Server
 * @author Mic, 2010
 */
class Site_Server_Arena
{
	/**
	 * Минимальный рейтинг, ниже которого команды арены не показываются по умолчанию.
	 * @var integer
	 */
	const DEFAULT_ARENA_RATING						= 1251;
	
	/**
	 * Вся информация об арене.
	 * @var array
	 */
	protected static $_data;

	/**
	 * Загружает информацию про арену.
	 */
	protected static function _Load()
	{
		$result = Env::Get()->cache->Load('server/arena', 3600);
		if ($result === null)
		{
			//Команды:
			$list = Env::Get()->db->Get('game')->Query('
				SELECT
					at.type,
					at.arenateamid,
					at.name,
					c.name AS captain,
					IF(c.race IN('.User_Character_Race::GetAllianceAsString().'), 0, 1) AS faction,
					ats.rating
				FROM arena_team AS at
					INNER JOIN characters AS c ON(c.guid = at.captainguid)
					INNER JOIN arena_team AS ats ON(ats.arenateamid = at.arenateamid)
				ORDER BY
					at.type,
					ats.rating DESC
			')->FetchAll();
			
			$result = array();
			foreach ($list as $team)
			{
				$result[$team['arenateamid']] = $team;
			}
			
			//Игроки:
			$members = Env::Get()->db->Get('game')->Query('
				SELECT
					atm.*,
					c.name,
					c.level,
					c.gender,
					c.race,
					IF(c.race IN('.User_Character_Race::GetAllianceAsString().'), 0, 1) AS faction,
					c.class
				FROM arena_team_member AS atm
					INNER JOIN characters AS c USING(guid)
				ORDER BY c.name
			')->FetchAll();
			
			foreach ($members as $member)
			{
				$result[$member['arenateamid']]['members'][] = $member;
			}
			
			Env::Get()->cache->Save(null, $result);
		}
		
		self::$_data = $result;
	}
	
	/**
	 * Возвращает список команд.
	 * @param boolean $needAll
	 * @return array
	 */
	public static function GetTeams($needAll)
	{
		self::_Load();
		
		$teams = self::$_data;
		
		//Фильтруем по рейтингу:
		if ($needAll == false)
		{
			$teams = array_filter($teams, create_function('$team', 'return $team["rating"] >= '.self::DEFAULT_ARENA_RATING.';'));
		}
		
		//Группируем по типу:
		$result = array();
		foreach ($teams as $team)
		{
			$result[$team['type']][] = $team;
		}
		
		return $result;
	}
	
	/**
	 * Возвращает информацию про команду.
	 * @param integer $id
	 * @return array
	 */
	public static function GetTeam($id)
	{
		self::_Load();
		return isset(self::$_data[$id]) ? self::$_data[$id] : null;
	}
};
