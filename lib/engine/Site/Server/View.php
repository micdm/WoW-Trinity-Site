<?php

/**
 * Визуализация всех разделов о сервере.
 * @author Mic, 2010
 */
class Site_Server_View extends Site_Main_View
{
	/**
	 * Определяет статус рилма.
	 * @return bool
	 */
	private static function _IsRealmActive()
	{
		$result = Env::Get()->cache->Load('server/index/status', 30);
		if ($result === null)
		{
			try
			{
				//Пытаемся подключиться:
				$server = Env::Get()->config->Get('server');
				if ($result = fsockopen($server['host'], $server['port']))
				{
					fclose($result);
				}
			}
			catch (Exception_Php_Warning $e)
			{
				$result = false;
			}

			Env::Get()->cache->Save(null, intval($result));
		}
		
		return ($result == true);
	}
	
	/**
	 * Загружает количество всех созданных аккаунтов.
	 * @return integer
	 */
	private static function _LoadAccountsTotalCount()
	{
		$result = Env::Get()->cache->Load('server/index/accounts', 3600);
		if (empty($result))
		{
			$result = Env::Get()->db->Get('game')->Query('
				SELECT COUNT(*)
				FROM #realm.account
			')->FetchOne();
			
			Env::Get()->cache->Save(null, $result);
		}
		
		return $result;
	}
	
	/**
	 * Загружает количество всех созданных персонажей.
	 * @return integer
	 */
	private static function _LoadCharactersTotalCount()
	{
		$result = Env::Get()->cache->Load('server/index/characters', 3600);
		if (empty($result))
		{
			$result = Env::Get()->db->Get('game')->Query('
				SELECT COUNT(*)
				FROM characters
			')->FetchOne();
			
			Env::Get()->cache->Save(null, $result);
		}
		
		return $result;
	}
	
	/**
	 * Загружает список персонажей онлайн.
	 * @return array
	 */
	private static function _LoadCharactersOnline()
	{
		$result = Env::Get()->cache->Load('server/online', 30);
		if (empty($result))
		{
			$result = Env::Get()->db->Get('game')->Query('
				SELECT
					c.name,
					c.level,
					c.gender,
					c.race,
					IF(c.race IN('.User_Character_Race::GetAllianceAsString().'), 0, 1) AS faction,
					c.class,
					sz.name AS location
				FROM characters AS c
					INNER JOIN #site.site_zones AS sz ON(sz.id = c.zone)
				WHERE TRUE
					AND c.online = 1
					AND (c.extra_flags & 1) = 0
				ORDER BY c.name
			')->FetchAll();
			
			Env::Get()->cache->Save(null, $result);
		}
		
		return $result;
	}
	
	/**
	 * Загружает список самых продвинутых в PvP персонажей.
	 * @return array
	 */
	private static function _LoadPvpTop()
	{
		$result = Env::Get()->cache->Load('server/pvp', 3600);
		if (empty($result))
		{
			$result = Env::Get()->db->Get('game')->Query('
				SELECT
					name,
					level,
					gender,
					race,
					IF(race IN('.User_Character_Race::GetAllianceAsString().') , 0 , 1) AS faction,
					class,
					totalKills AS kills,
					totalHonorPoints AS honor,
					arenaPoints AS arena
				FROM characters
				WHERE level >= 70
				ORDER BY
					kills DESC,
					honor DESC,
					name
				LIMIT 300
			')->FetchAll();
			
			Env::Get()->cache->Save(null, $result);
		}

		return $result;
	}
	
	/**
	 * Загружает список крупных гильдий (либо всех, если необходимо).
	 * @param bool $needAll
	 * @return array
	 */
	private static function _LoadGuilds($needAll = false)
	{
		$result = Env::Get()->cache->Load('server/guild/list/'.intval($needAll), 3600);
		if (empty($result))
		{
			$result = Env::Get()->db->Get('game')->Query('
				SELECT
					g.guildid,
					g.name,
					c.name AS leader,
					IF(c.race IN('.User_Character_Race::GetAllianceAsString().'), 0, 1) AS faction,
					COUNT(gm.guid) AS count
				FROM guild AS g
					INNER JOIN characters AS c ON(c.guid = g.leaderguid)
					INNER JOIN guild_member AS gm ON(gm.guildid = g.guildid)
				GROUP BY g.guildid
				'.($needAll ? '' : 'HAVING count >= 10').'
				ORDER BY count DESC
			')->FetchAll();
			
			Env::Get()->cache->Save(null, $result);
		}

		return $result;
	}
	
	/**
	 * Загружает информацию о конкретной гильдии.
	 * @param integer $id
	 * @return array
	 */
	private static function _LoadGuildInfo($id)
	{
		$result = Env::Get()->cache->Load('server/guild/'.$id, 3600);
		if (empty($result))
		{
			//Информация о гильдии:
			$info = Env::Get()->db->Get('game')->Query('
				SELECT
					name,
					createdate
				FROM guild
				WHERE guildid = :guild
			', array(
				'guild' => array('d', $id)
			))->FetchRow();
			
			//Есть такая гильдия?
			if (empty($info))
			{
				throw new Exception_Http_NotFound('гильдия с идентификатором '.$id.' не найдена');
			}
			
			//Члены гильдии:
			$members = Env::Get()->db->Get('game')->Query('
				SELECT
					c.guid,
					gr.rname,
					c.name,
					c.level,
					c.gender,
					c.race,
					IF(c.race IN('.User_Character_Race::GetAllianceAsString().'), 0, 1) AS faction,
					c.class,
					c.online
				FROM guild_member AS gm
					INNER JOIN guild_rank AS gr ON(TRUE
						AND gr.guildid = gm.guildid
						AND gr.rid = gm.rank
					)
					INNER JOIN characters AS c ON(c.guid = gm.guid)
				WHERE gm.guildid = :guild
				ORDER BY gm.rank
			', array(
				'guild' => array('d', $id)
			))->FetchAll();
			
			$result = array(
				'info' => $info,
				'members' => $members
			);

			Env::Get()->cache->Save(null, $result);
		}

		return $result;
	}

	/**
	 * Загружает забаненные аккаунты и IP-адреса.
	 * @return array
	 */
	private static function _LoadBanlist()
	{
		$result = Env::Get()->cache->Load('server/banlist', 600);
		if (empty($result))
		{
			//Забаненные аккаунты:
			$accounts = Env::Get()->db->Get('game')->Query('
				SELECT
					ab.id,
					IF(ab.bandate, ab.bandate, 0) AS bandate,
					FLOOR((ab.unbandate - UNIX_TIMESTAMP()) / 3600) AS unbandate,
					IF(ab.bandate = ab.unbandate, 1, 0) AS permanent,
					ab.banreason,
					a.username
				FROM #realm.account_banned AS ab
					INNER JOIN #realm.account AS a USING(id)
				WHERE ab.active = 1
				GROUP BY ab.id
				ORDER BY a.username
			')->FetchAll();
			
			//IP-адреса:
			$ips = Env::Get()->db->Get('game')->Query('
				SELECT
					ip,
					bandate,
					FLOOR((unbandate - UNIX_TIMESTAMP()) / 3600) AS unbandate,
					IF(bandate = unbandate, 1, 0) AS permanent,
					bannedby,
					banreason
				FROM #realm.ip_banned
				ORDER BY ip
			')->FetchAll();
			
			$result = array(
				'accounts' => $accounts,
				'ips' => $ips
			);

			Env::Get()->cache->Save(null, $result);
		}

		return $result;
	}
	
	/**
	 * Загружает список персонажей администрации.
	 * @return array
	 */
	private static function _LoadGms()
	{
		$result = Env::Get()->cache->Load('server/gms', 300);
		if (empty($result))
		{
			$result = Env::Get()->db->Get('game')->Query('
				SELECT
					c.name,
					c.level,
					c.gender,
					c.race,
					IF(c.race IN('.User_Character_Race::GetAllianceAsString().'), 0, 1) AS faction,
					c.class,
					FLOOR((UNIX_TIMESTAMP() - c.logout_time) / 3600) AS logout,
					c.online,
					aa.gmlevel
				FROM characters AS c
					INNER JOIN #realm.account AS a ON(a.id = c.account)
					INNER JOIN #realm.account_access AS aa ON(aa.id = a.id)
					INNER JOIN #site.site_operation_masking AS sov ON(sov.guid = c.guid)
				WHERE UNIX_TIMESTAMP() - c.logout_time <= 168 * 3600
				ORDER BY
					c.online DESC,
					c.logout_time DESC
			')->FetchAll();
			
			Env::Get()->cache->Save(null, $result);
		}

		return $result;
	}
	
	/**
	 * Загружает персонажей с наибольшим количеством достижений по группам.
	 * @return array
	 */
	private static function _LoadAchievements()
	{
		$result = Env::Get()->cache->Load('server/achievements', 3600);
		if (empty($result))
		{
			$result = Env::Get()->db->Get('game')->Query('
				SELECT
					c.guid,
					c.name,
					c.gender,
					c.race,
					IF(c.race IN('.User_Character_Race::GetAllianceAsString().'), 0, 1) AS faction,
					c.class,
					COUNT(ca.achievement) AS count
				FROM characters AS c
					INNER JOIN character_achievement AS ca USING(guid)
				GROUP BY c.guid
				ORDER BY count DESC
				LIMIT 20
			')->FetchAll();
			
			Env::Get()->cache->Save(null, $result);
		}

		return $result;
	}
	
	public function Index($args, $params)
	{
		//Статус рилма:
		$this->_context->Set('realm', self::_IsRealmActive());
		
		//Всего аккаунтов:
		$this->_context->Set('accounts', self::_LoadAccountsTotalCount());
		
		//Всего персонажей:
		$this->_context->Set('characters', self::_LoadCharactersTotalCount());
		
		return $this->_Render('server/index.htm');
	}
	
	public function Online($args, $params)
	{
		$this->_context->Set('list', self::_LoadCharactersOnline());
		return $this->_Render('server/online.htm');
	}
	
	public function Pvp($args, $params)
	{
		$this->_context->Set('list', self::_LoadPvpTop());
		return $this->_Render('server/pvp.htm');
	}
	
	public function Guild($args, $params)
	{
		if (empty($args['id']))
		{
			//Нужно ли загрузить все гильдии:
			$needAll = (empty($args['all']) == false);
			$this->_context->Set('needAll', $needAll);
			
			//Загружаем:
			$this->_context->Set('list', self::_LoadGuilds($needAll));
			
		}
		else
		{
			//Показываем информацию о конкретной гильдии:
			$this->_context->Set('guild', self::_LoadGuildInfo($args['id']));
		}

		return $this->_Render('server/guild.htm');
	}
	
	public function TopTwenty($args, $params)
	{
		$this->_context->Set('top', Site_Server_Top::Get());
		return $this->_Render('server/top20.htm');
	}
	
	public function Arena($args, $params)
	{
		//Минимальный рейтинг:
		$this->_context->Set('minRating', Site_Server_Arena::DEFAULT_ARENA_RATING);
		
		if (empty($args['id']))
		{
			//Нужно ли загрузить все команды:
			$needAll = (empty($args['all']) == false);
			$this->_context->Set('needAll', $needAll);
			
			//Загружаем:
			$this->_context->Set('list', Site_Server_Arena::GetTeams($needAll));
		}
		else
		{
			//Показываем информацию о конкретной команде:
			$team = Site_Server_Arena::GetTeam($args['id']);
			if ($team === null)
			{
				throw new Exception_Http_NotFound('команда '.intval($args['id']).' не найдена');
			}
			
			$this->_context->Set('team', $team);
		}

		return $this->_Render('server/arena.htm');
	}
	
	public function Banlist($args, $params)
	{
		$this->_context->Set('banlist', self::_LoadBanlist());
		return $this->_Render('server/banlist.htm');
	}
	
	public function Gms($args, $params)
	{
		$this->_context->Set('list', self::_LoadGms());
		return $this->_Render('server/gms.htm');
	}
	
	public function Achievement($args, $params)
	{
		$this->_context->Set('list', self::_LoadAchievements());
		return $this->_Render('server/achievements.htm');
	}

	public function Character($args)
	{
		$this->_RunOperation('searching');
		
		if (isset($args['id']))
		{
			$character = Env::Get()->user->GetAccount()->GetCharacters()->Find($args['id']);
			if (empty($character))
			{
				throw new Exception_Http_NotFound();
			}
			
			$this->_context->Set('character', $character);
		}
		
		return $this->_Render('server/character.htm');
	}
};
