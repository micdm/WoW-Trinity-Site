<?php

/**
 * Реферальная система.
 * @package User
 * @author Mic, 2010
 */
class User_ReferralSystem
{
	/**
	 * Минимальный уровень персонажа, с которого будут начислять бонусы за время онлайн.
	 * @var integer
	 */
	const MIN_LEVEL_FOR_ONLINE_BONUS				= 15;
	
	/**
	 * Количество очков за один час онлайна реферала.
	 * @var integer
	 */
	const POINTS_PER_HOUR							= 3;
	
	/**
	 * Количество очков за один уровень реферала.
	 * @var integer
	 */
	const POINTS_PER_LEVEL							= 10;

	/**
	 * Количество очков за одно достижение реферала.
	 * @var integer
	 */
	const POINTS_PER_ACHIEVEMENT					= 10;

	/**
	 * Количество медных за одно очко.
	 * @var integer
	 */
	const COPPER_PER_POINT							= 10000;
	
	/**
	 * Минимальное количество золота, которое может быть начислено с аккаунта:
	 * @var integer
	 */
	const MIN_INCOME								= 50000;
	
	/**
	 * Переменная, в которую записывается дата последнего награждения.
	 * @var string
	 */
	const LAST_REWARDED_VARIABLE					= 'referral_last_rewarded';
	
	/**
	 * Общее количество реферальных аккаунтов.
	 * @var integer
	 */
	private static $_count;
	
	/**
	 * Весь список персонажей, которые получат бонусы.
	 * @var array
	 */
	private static $_bonuses;
	
	/**
	 * Просуммированные бонусы для каждого реферального хозяина.
	 * @var array
	 */
	private static $_summary;
	
	/**
	 * Сколько всего золота будет начислено.
	 * @var integer
	 */
	private static $_total;

	/**
	 * Загружает и обрабатывает информацию о рефералах.
	 */
	protected static function _Load()
	{
		//Выходим, если уже все посчитано ранее:
		if (self::$_bonuses !== null)
		{
			return;
		}
		
		$db = Env::Get()->db->Get('game');
		
		//Количество реферальных аккаунтов:
		self::$_count = $db->Query('
			SELECT COUNT(*)
			FROM #site.site_referrals			
		')->FetchOne();

		//Кому что начислить:
		$bonuses = $db->Query('
			SELECT
				t1.account AS referral,
				t2.guid AS ownerGuid,
				t2.name AS ownerName,
				t2.level AS ownerLevel, (
					/* Бонус за проведенное в игре время */
					SELECT COALESCE(SUM(IF(c.level > :minLevelForOnlineBonus, c.totaltime, 0)), 0) * :pointsPerHour / 3600
					FROM characters AS c
						LEFT JOIN #site.site_operation_transfer_complete AS sotc ON(sotc.guid = c.guid)
					WHERE TRUE
						AND c.account = t1.account
						AND sotc.guid IS NULL
				) + (
					/* Бонус за уровни */
					SELECT COALESCE(SUM(IF(c.class = :deathknightClass, c.level - :minLevelForDeathknight, c.level)), 0) * :pointsPerLevel
					FROM characters AS c
						LEFT JOIN #site.site_operation_transfer_complete AS sotc ON(sotc.guid = c.guid)
					WHERE TRUE
						AND c.account = t1.account
						AND sotc.guid IS NULL
				) + (
					/* Бонус за достижения */
					SELECT COALESCE(COUNT(*), 0) * :pointsPerAchievement
					FROM character_achievement AS ca
						INNER JOIN characters AS c ON(c.guid = ca.guid)
						LEFT JOIN #site.site_achvs AS sa ON(sa.id = ca.achievement)
						LEFT JOIN #site.site_operation_transfer_complete AS sotc ON(sotc.guid = c.guid)
					WHERE TRUE
						AND sa.category NOT IN(97, 14777, 14778, 14779, 14780) /*категории исследования, боремся с флайхакерами*/
						AND c.account = t1.account
						AND sotc.guid IS NULL
				) - (
					/* Уже полученные очки */
					SELECT COALESCE(SUM(points), 0)
					FROM #site.site_referrals_info
					WHERE account = t1.account
				) AS points
				FROM #site.site_referrals AS t1
					INNER JOIN characters AS t2 ON(t2.guid = t1.to_character)
					LEFT JOIN #realm.account_banned AS t3 ON(t3.id = t1.account)
				WHERE COALESCE(t3.active, FALSE) = FALSE
				GROUP BY t1.account
				HAVING points > 0
				ORDER BY points DESC
		', array(
			'minLevelForOnlineBonus' => array('d', self::MIN_LEVEL_FOR_ONLINE_BONUS),
			'deathknightClass' => array('d', User_Character_Class::DEATHKNIGHT),
			'minLevelForDeathknight' => array('d', Env::Get()->config->Get('minLevelForDeathknight')),

			'pointsPerHour' => array('d', self::POINTS_PER_HOUR),
			'pointsPerLevel' => array('d', self::POINTS_PER_LEVEL),
			'pointsPerAchievement' => array('d', self::POINTS_PER_ACHIEVEMENT),
		))->FetchAll();
		
		self::$_bonuses = $bonuses;
		
		//Суммируем бонусы для каждого реферального хозяина:
		self::_CalculateSummary();
	}
	
	/**
	 * Суммирует все бонусы для каждого персонажа.
	 * @return array
	 */
	private static function _CalculateSummary()
	{
		//Рассчитываем бонусы:
		$summary = array();
		$total = 0;
		
		foreach (self::$_bonuses as $i => $referral)
		{
			//Бонус от реферала в меди:
			$income = floor($referral['ownerLevel'] / 80 * $referral['points'] * self::COPPER_PER_POINT);
			
			//Если бонус меньше минимума, пропускаем его:
			if ($income < self::MIN_INCOME)
			{
				unset(self::$_bonuses[$i]);
				continue;
			}

			self::$_bonuses[$i]['income'] = $income;
			
			//Суммируем:
			$guid = $referral['ownerGuid'];
			if (empty($summary[$guid]))
			{	
				$summary[$guid] = array(
					'name' => $referral['ownerName'],
					'value' => 0
				);
			}

			$summary[$guid]['value'] += $income;
			
			//Всего бонусов к начислению:
			$total += $income;
		}
		
		self::$_summary = $summary;
		self::$_total = $total;
	}
	
	/**
	 * Возвращает статистику по реферальной системе.
	 * @return array
	 */
	public static function GetStats()
	{
		self::_Load();
		return array(
			'count' => self::$_count,
			'summary' => self::$_summary,
			'total' => round(self::$_total / 10000)
		);
	}
	
	/**
	 * Возвращает время последнего награждения.
	 * @return integer
	 */
	public static function GetLastRewarded()
	{
		$date = Util_Variables::Get(self::LAST_REWARDED_VARIABLE);
		return $date ? strtotime($date) : 0;
	}
	
	/**
	 * Рассылает бонусы персонажам в игру.
	 */
	public static function SendBonuses()
	{
		self::_Load();

		//Рассылаем бонусы:
		foreach (self::$_summary as $guid => $data)
		{
			//Пытаемся отослать письмо в игру:
			$mail = Env::Get()->user->GetAccount()->GetCharacters()->Find($guid)->CreateMail();
			$mail
				->SetSubject('Доход с реферала')
				->SetBody($data['name'].', благодарим Вас за использование нашей реферальной системы!')
				->SetMoney($data['value'])
				->Send();
			
		}
		
		//Записываем в базу информацию про каждый реферальный аккаунт:
		foreach (self::$_bonuses as $referral)
		{
			if ($referral['income'])
			{
				Env::Get()->db->Get('game')->Query('
					INSERT INTO #site.site_referrals_info (account, points, money)
					VALUES (:account, :points, :money)
				', array(
					'account' => array('d', $referral['referral']),
					'points' => array('d', $referral['points']),
					'money' => array('d', $referral['income']),
				));
			}
		}
		
		Util_Variables::Set(self::LAST_REWARDED_VARIABLE, date('Y-m-d H:i:s'));
		throw new Exception_Http_Redirected();
	}
	
	/**
	 * Загружает список рефералов для аккаунта (по умолчанию - текущего).
	 * @param User_Account $account
	 * @return array
	 */
	public static function GetReferrals($account = null)
	{
		if (empty($account))
		{
			$account = Env::Get()->user->GetAccount();
		}
		
		return Env::Get()->db->Get('game')->Query('
			SELECT
				c2.name,
				c2.level,
				c2.gender,
				c2.race,
				IF(c2.race IN('.User_Character_Race::GetAllianceAsString().'), 0, 1) AS faction,
				c2.class
			FROM #site.site_referrals AS sr
				INNER JOIN characters AS c1 ON(c1.guid = sr.to_character)
				INNER JOIN characters AS c2 ON(c2.account = sr.account)
				LEFT JOIN #realm.account_banned AS ab ON(ab.id = sr.account)
				LEFT JOIN #site.site_operation_transfer_complete AS sotc ON(sotc.guid = c2.guid)
			WHERE TRUE
				AND c1.account = :account
				
				/* Не показываем забаненных и перенесенных */
				AND COALESCE(ab.active, FALSE) = FALSE
				AND sotc.guid IS NULL
			ORDER BY c1.level DESC
		', array(
			'account' => array('d', $account->GetId())
		))->FetchAll();
	}
	
	/**
	 * Загружает количество золота, полученного с рефералов для текущего аккаунта.
	 * @return integer
	 */
	public static function GetMoneyGained()
	{
		return Env::Get()->db->Get('game')->Query('
			SELECT COALESCE(FLOOR(SUM(sri.money) / 10000), 0)
			FROM #site.site_referrals AS sr
				INNER JOIN characters AS c ON(c.guid = sr.to_character)
				INNER JOIN #site.site_referrals_info AS sri ON(sri.account = sr.account)
			WHERE c.account = :account
		', array(
			'account' => array('d', Env::Get()->user->GetAccount()->GetId())
		))->FetchOne();
	}
	
	/**
	 * Прикрепляет аккаунт к реферал-хозяину.
	 * @param User_Character $owner
	 * @param User_Account $account
	 */
	public static function ApplyReferral($owner, $account)
	{
		Env::Get()->db->Get('game')->Query('
			INSERT INTO #site.site_referrals (account, to_character)
			VALUES (:account, :owner) 
		', array(
			'account' => array('d', $account->GetId()),
			'owner' => array('d', $owner->GetGuid()),
		));
	}
};
