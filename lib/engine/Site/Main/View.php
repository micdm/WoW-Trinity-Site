<?php

/**
 * Визуализация страниц в корне сайта.
 * @package Site_Main
 * @author Mic, 2010
 */
class Site_Main_View extends View
{
	/**
	 * Загружает общее количество игроков онлайн + количество игроков Альянса.
	 * @return array
	 */
	private static function _LoadOnline()
	{
		$online = Env::Get()->cache->Load('main/online', 30);
		if ($online === null)
		{
			$online = Env::Get()->db->Get('game')->Query('
				SELECT
					COUNT(*) AS total,
					SUM(IF(race IN('.User_Character_Race::GetAllianceAsString().'), 1, 0)) AS alliance
				FROM characters
				WHERE online = 1
			')->FetchRow();
			
			//Вычисляем и Орду:
			$online['alliance'] = intval($online['alliance']);
			$online['horde'] = $online['total'] - $online['alliance'];
			
			Env::Get()->cache->Save(null, $online);
		}
		
		return $online;
	}
	
	/**
	 * Загружает аптайм в часах-минутах.
	 * @return array
	 */
	private static function _LoadUptime()
	{
		$uptime = Env::Get()->cache->Load('main/uptime', 60);
		if (empty($uptime))
		{
			$uptime = Env::Get()->db->Get('game')->Query('
				SELECT
					FLOOR(uptime / 3600) AS hours,
					FLOOR((uptime - FLOOR(uptime / 3600) * 3600) / 60) AS minutes
				FROM #realm.uptime
				WHERE realmid = :realm
				ORDER BY starttime DESC
				LIMIT 1
			', array(
				'realm' => array('d', Env::Get()->config->Get('realm'))
			))->FetchRow();
			
			Env::Get()->cache->Save(null, $uptime);
		}
		
		return $uptime;
	}
	
	public function RunMethod($method, $args = null, $params = null)
	{
		$config = Env::Get()->config;
		$this->_context
			->Set('online', self::_LoadOnline())
			->Set('uptime', self::_LoadUptime())
			->Set('client', $config->Get('client'))
			->Set('rates', $config->Get('rates'))
			->Set('mmotop', Site_Util::GetMmotopStats())
			->Set('announce', Env::Get()->forum->GetRandomAnnounce())
			->Set('races', $config->Get('races', 'game'))
			->Set('classes', $config->Get('classes', 'game'))
			->Set('levels', $config->Get('levels', 'game'))
			->Set('user', Env::Get()->user->GetAccount())
			->Set('input', Env::Get()->request->GetInput());
		
		return parent::RunMethod($method, $args, $params);
	}
	
	public function Welcome($args, $params)
	{
		//Новости:
		$this->_context->Set('news', Env::Get()->forum->LoadNews());
		
		//Сообщения с форума:
		$this->_context->Set('messages', Env::Get()->forum->LoadLastMessages());
		
		return $this->_Render('main/welcome.htm');
	}

	public function News($args, $params)
	{
		//Новости:
		$this->_context->Set('news', Env::Get()->forum->LoadNews(100));

		return $this->_Render('main/news.htm');
	}
	
	public function Registration($args, $params)
	{
		$operation = $this->_RunOperation('registration');
		
		//Нужна ли капча?
		$this->_context->Set('needCaptcha', $operation->NeedCaptcha());
		
		//Включены ли премиум-аккаунты?
		$this->_context->Set('premium', Env::Get()->config->Get('premiumAccountPeriod'));
		return $this->_Render('main/reg.htm');
	}
	
	public function Files($args, $params)
	{
		return $this->_Render('main/files.htm');
	}
	
	public function Recovery($args, $params)
	{
		$this->_RunOperation('recovery');
		return $this->_Render('main/recovery.htm');
	}
	
	public function Realmlist()
	{
		Http_Header_ContentType::Set('text/wtf');
		return $this->_Render('main/realmlist.htm');
	}

	public function Warmor()
	{
		return $this->_Render('main/warmor.htm');
	}
};
