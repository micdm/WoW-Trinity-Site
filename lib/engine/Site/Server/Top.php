<?php

/**
 * Загрузка топов персонажей.
 * @author MIc, 2010
 */
class Site_Server_Top
{
/**
	 * Загружает топ-20 по времени онлайн.
	 * @return array
	 */
	private static function _LoadByTime()
	{
		return Env::Get()->db->Get('game')->Query('
			SELECT DISTINCT
				c.guid,
				c.name,
				c.level,
				c.gender,
				c.race,
				IF(c.race IN('.User_Character_Race::GetAllianceAsString().'), 0, 1) AS faction,
				c.class,
				FLOOR(c.totaltime / 3600) AS time,
				FLOOR(c.totaltime / 3600 / 24) AS days
			FROM characters AS c
				INNER JOIN #realm.account AS a ON(a.id = c.account)
				LEFT JOIN #realm.account_access AS aa ON(aa.id = a.id)
				LEFT JOIN #realm.account_banned AS ab ON(ab.id = a.id)
				LEFT JOIN #site.site_operation_masking AS sov ON(sov.guid = c.guid)
			WHERE TRUE
				AND c.account NOT IN(1, 43189)
				AND (FALSE
					OR ab.active IS NULL
					OR ab.active = 0
				)
				AND (FALSE 
					OR aa.gmlevel = 0
					OR sov.guid IS NULL
				)
			ORDER BY time DESC
			LIMIT 20
		')->FetchAll();
	}
	
	/**
	 * Загружает топ-20 по здоровью.
	 * @return array
	 */
	private static function _LoadByHealth()
	{
		return Env::Get()->db->Get('game')->Query('
			SELECT DISTINCT
				c.guid,
				c.name,
				c.level,
				c.gender,
				c.race,
				IF(c.race IN('.User_Character_Race::GetAllianceAsString().'), 0, 1) AS faction,
				c.class,
				c.health
			FROM characters AS c
				INNER JOIN #realm.account AS a ON(a.id = c.account)
				LEFT JOIN #realm.account_access AS aa ON(aa.id = a.id)
				LEFT JOIN #realm.account_banned AS ab ON(ab.id = a.id)
				LEFT JOIN #site.site_operation_masking AS sov ON(sov.guid = c.guid)
			WHERE TRUE
				AND c.account NOT IN(1, 43189)
				AND (FALSE
					OR ab.active IS NULL
					OR ab.active = 0
				)
				AND (FALSE 
					OR aa.gmlevel = 0
					OR sov.guid IS NULL
				)
				AND c.level != 0
			ORDER BY health DESC
			LIMIT 20
		')->FetchAll();
	}
	
	/**
	 * Загружает топ-20 по золоту.
	 * @return array
	 */
	private static function _LoadByGold()
	{
		return Env::Get()->db->Get('game')->Query('
			SELECT DISTINCT
				c.guid,
				c.name,
				c.level,
				c.gender,
				c.race,
				IF(c.race IN('.User_Character_Race::GetAllianceAsString().'), 0, 1) AS faction,
				c.class,
				(c.money / 10000) AS money
			FROM characters AS c
				INNER JOIN #realm.account AS a ON(a.id = c.account)
				LEFT JOIN #realm.account_access AS aa ON(aa.id = a.id)
				LEFT JOIN #realm.account_banned AS ab ON(ab.id = a.id)
				LEFT JOIN #site.site_operation_masking AS sov ON(sov.guid = c.guid)
			WHERE TRUE
				AND c.account NOT IN(1, 43189)
				AND (FALSE
					OR ab.active IS NULL
					OR ab.active = 0
				)
				AND (FALSE 
					OR aa.gmlevel = 0
					OR sov.guid IS NULL
				)
			ORDER BY money DESC
			LIMIT 20
		')->FetchAll();
	}
	
	/**
	 * Загружает общий топ-20 персонажей.
	 * @return array
	 */
	public static function Get()
	{
		$result = Env::Get()->cache->Load('server/top20', 3600);
		if (empty($result))
		{
			//Топ по времени онлайн:
			$result['byTime'] = self::_LoadByTime();
			
			//Топ по здоровью:
			$result['byHealth'] = self::_LoadByHealth();
			
			//Топ по золоту:
			$result['byGold'] = self::_LoadByGold();
			
			Env::Get()->cache->Save(null, $result);
		}
		
		return $result;
	}
};
