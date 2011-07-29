<?php

/**
 * Калькулятор рейтингов персонажа.
 * @author Mic, 2010
 * @package User_Character
 */
class User_Character_Rating extends ArrayType
{
	/**
	 * Минимальный уровень, с которого начинается участие в рейтинге.
	 * @var integer
	 */
	const MIN_LEVEL_NEEDED = 80;
	
	const POINTS_PER_ACHIEVEMENT = 9;
	const POINTS_PER_FRIEND = 18;
	const POINTS_PER_GOLD = 0.000011;
	const POINTS_PER_SECOND_ONLINE = 0.00028;
	const POINTS_PER_PVP_KILL = 0.01;
	const POINTS_PER_REFERRAL = 18;
	const POINTS_PER_QUEST = 6;
	
	/**
	 * Идентификатор целевого персонажа.
	 * @var integer
	 */
	protected $_character;
	
	/**
	 * Все загруженные рейтинги.
	 * @var array
	 */
	protected $_all;
	
	/**
	 * @param integer $id
	 */
	public function __construct($id)
	{
		$this->_character = $id;
	}

	/**
	 * Загружает рейтинг по достижениям.
	 */
	protected function _LoadAchievementsScore()
	{
		$count = Env::Get()->db->Get('game')->Query('
			SELECT COUNT(*)
			FROM character_achievement
			WHERE guid = :id
		', array(
			'id' => array('d', $this->_character)
		))->FetchOne();
		
		$this->_all['achievements'] = array(
			'name' => 'Достижения',
			'value' => $count * self::POINTS_PER_ACHIEVEMENT
		);
	}
	
	/**
	 * Загружает рейтинг по друзьям.
	 */
	protected function _LoadFriendsScore()
	{
		$count = Env::Get()->db->Get('game')->Query('
			SELECT COUNT(*)
			FROM character_social
			WHERE TRUE
				AND friend = :id
				AND flags = 0
		', array(
			'id' => array('d', $this->_character)
		))->FetchOne();
		
		$this->_all['friends'] = array(
			'name' => 'Друзья',
			'value' => $count * self::POINTS_PER_FRIEND
		);
	}
	
	/**
	 * Загружает рейтинг по золоту.
	 */
	protected function _LoadMoneyScore()
	{
		$money = Env::Get()->db->Get('game')->Query('
			SELECT money
			FROM characters
			WHERE guid = :id
		', array(
			'id' => array('d', $this->_character)
		))->FetchOne();
		
		$this->_all['money'] = array(
			'name' => 'Деньги',
			'value' => $money * self::POINTS_PER_GOLD
		);
	}
	
	/**
	 * Загружает рейтинг по времени онлайн.
	 */
	protected function _LoadOnlineScore()
	{
		$time = Env::Get()->db->Get('game')->Query('
			SELECT totaltime
			FROM characters
			WHERE guid = :id
		', array(
			'id' => array('d', $this->_character)
		))->FetchOne();
		
		$this->_all['online'] = array(
			'name' => 'Время онлайн',
			'value' => $time * self::POINTS_PER_SECOND_ONLINE
		);
	}
	
	/**
	 * Загружает рейтинг по PvP.
	 */
	protected function _LoadPvpScore()
	{
		$kills = Env::Get()->db->Get('game')->Query('
			SELECT totalKills
			FROM characters
			WHERE guid = :id
		', array(
			'id' => array('d', $this->_character)
		))->FetchOne();
		
		$this->_all['pvp'] = array(
			'name' => 'PvP',
			'value' => $kills * self::POINTS_PER_PVP_KILL
		);
	}
	
	/**
	 * Загружает рейтинг по приглашенным игрокам.
	 */
	protected function _LoadReferralsScore()
	{
		$count = Env::Get()->db->Get('game')->Query('
			SELECT COUNT(*)
			FROM #site.site_referrals
			WHERE to_character = :id
		', array(
			'id' => array('d', $this->_character)
		))->FetchOne();
		
		$this->_all['referrals'] = array(
			'name' => 'Реферальная система',
			'value' => $count * self::POINTS_PER_REFERRAL
		);
	}
	
	/**
	 * Загружает рейтинг по квестам.
	 */
	protected function _LoadQuestsScore()
	{
		$count = Env::Get()->db->Get('game')->Query('
			SELECT COUNT(*)
			FROM character_queststatus
			WHERE TRUE
				AND guid = :id
				AND status = 1
		', array(
			'id' => array('d', $this->_character)
		))->FetchOne();
		
		$this->_all['quests'] = array(
			'name' => 'Квесты',
			'value' => $count * self::POINTS_PER_QUEST
		);
	}
	
	/**
	 * Подсчитывает суммарный счет.
	 */
	protected function _CalculateTotalScore()
	{
		$total = 0;
		foreach ($this->_all as &$rating)
		{
			$rating['value'] = round($rating['value']);
			$total += $rating['value'];
		}
		
		$this->_all['total'] = array(
			'name' => 'Всего',
			'value' => $total
		);
	}
	
	/**
	 * Загружает все рейтинги.
	 */
	protected function _LoadAll()
	{
		$this->_all = Env::Get()->cache->Load('character/rating/'.$this->_character, 3600);
		if ($this->_all !== null)
		{
			return;
		}

		$this->_LoadAchievementsScore();
		$this->_LoadFriendsScore();
		$this->_LoadMoneyScore();
		$this->_LoadOnlineScore();
		$this->_LoadPvpScore();
		$this->_LoadReferralsScore();
		$this->_LoadQuestsScore();
		$this->_CalculateTotalScore();

		Env::Get()->cache->Save(null, $this->_all);
	}
	
	/**
	 * @return array
	 */
	public function GetAll()
	{
		if ($this->_all === null)
		{
			$this->_LoadAll();
		}
		
		return $this->_all;
	}
	
	/**
	 * Доступны ли рейтинги для персонажа.
	 * @return bool
	 */
	public function IsAvailable()
	{
		$character = Env::Get()->user->GetAccount()->GetCharacters()->Find($this->_character);
		return $character->GetLevel() == self::MIN_LEVEL_NEEDED;
	}
}
