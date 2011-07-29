<?php

/**
 * Аккаунт пользователя.
 * @author Mic, 2010
 * @package User
 */
class User_Account extends ArrayType
{
	const LEVEL_PLAYER								= 0;
	const LEVEL_MODERATOR							= 1;
	const LEVEL_GAMEMASTER							= 2;
	const LEVEL_ADMINISTRATOR						= 3;
	
	const SEARCH_BY_ID								= 'id';
	const SEARCH_BY_USERNAME						= 'username';
	
	/**
	 * Информация об аккаунте.
	 * @var array
	 */
	protected $_info;
	
	/**
	 * Информация о чеках.
	 * @var User_Money_Cash
	 */
	protected $_cash;
	
	/**
	 * История банов.
	 * @var array
	 */
	protected $_bans;
	
	/**
	 * @param mixed $key
	 * @param string $searchBy
	 * @return User_Account
	 */
	public static function Factory($key = 0, $searchBy = self::SEARCH_BY_ID)
	{
		//Создаем анонимный аккаунт:
		if ($key === 0)
		{
			return new self();
		}
		
		//Пытаемся получить из хранилища:
		$result = Util_Map::Get('account/'.$searchBy.'/'.$key);
		if ($result === null)
		{
			$info = Env::Get()->db->Get('game')->Query('
				SELECT
					a.id,
					LOWER(a.username) AS username,
					LOWER(a.sha_pass_hash) AS hash,
					LOWER(a.email) AS email,
					a.locked,
					a.joindate AS joinDate,
					IF(a.last_login = \'0000-00-00 00:00:00\', 0, a.last_login) AS lastLoginDate,
					COALESCE(aa.gmlevel, 0) AS gmlevel,
					ab.active AS banned
				FROM #realm.account AS a
					LEFT JOIN #realm.account_access AS aa ON(aa.id = a.id)
					LEFT JOIN #realm.account_banned AS ab ON(TRUE
						AND ab.id = a.id
						AND ab.active = 1
					)
				WHERE a.'.$searchBy.' = :search
			', array(
				'search' => array('s', $key)
			))->FetchRow();
			
			$result = new self($info);
			if ($info)
			{
				Util_Map::Set('account/'.self::SEARCH_BY_ID.'/'.$info['id'], $result);
				Util_Map::Set('account/'.self::SEARCH_BY_USERNAME.'/'.$info['username'], $result);
			}
			else
			{
				Util_Map::Set('account/'.$searchBy.'/'.$key, $result);
			}
		}

		//Аккаунт не найден:
		if ($result->_IsNotLoaded())
		{
			throw new Exception_User_Account_NotFound();
		}

		return $result;
	}
	
	/**
	 * Ищет аккаунты по IP-адресу.
	 * @return array
	 */
	public static function FindByIp($ip)
	{
		$ids = Env::Get()->db->Get('game')->Query('
			SELECT id
			FROM #realm.account
			WHERE last_ip = :ip
			LIMIT 20
		', array(
			'ip' => array('s', $ip)
		))->FetchColumn();
		
		$result = array();
		foreach ($ids as $id)
		{
			$result[] = self::Factory($id);
		}
		
		return $result;
	}
	
	/**
	 * Сбрасывает кэш загруженных аккаунтов.
	 * Используется в тестах.
	 */
	public static function ResetState()
	{
		Util_Map::Clear('account');
	}
	
	/**
	 * @param array $info
	 */
	public function __construct($info = null)
	{
		//Некоторые стартовые значения:
		$this->_info = array(
			'id' => 0,
			'gmlevel' => 0,
			'locked' => 0
		);
		
		if ($info)
		{
			$this->_info = array_merge($this->_info, $info);
		}
	}
	
	/**
	 * Формат: Login (id). Либо id, если информация не загружена.
	 */
	public function __toString()
	{
		$result = $this->GetId();
		if ($result)
		{
			$result = $this->GetLogin().' ('.$result.')';
		}
		
		return $result;
	}
	
	/**
	 * Возвращает, загружен ли аккаунт.
	 * @return boolean
	 */
	protected function _IsNotLoaded()
	{
		return $this->_info['id'] == 0;
	}
	

	/**
	 * Создает новый аккаунт.
	 * @param string $username
	 * @param string $password
	 * @param string $email
	 * @return User_Account
	 */
	public function Create($username, $password, $email)
	{
		$db = Env::Get()->db->Get('game');
		$db->Query('
			INSERT INTO #realm.account (username, sha_pass_hash, email, last_ip)
			VALUES (LOWER(:username), :hash, LOWER(:email), :ip)
		', array(
			'username' => array('s', $username),
			'hash' => array('s', $this->_GenerateHash($password, $username)),
			'email' => array('s', $email),
			'ip' => array('s', Env::Get()->request->GetIp()),
		));
		
		return Env::Get()->user->Find($db->GetLastId());
	}
	
	
	/**
	 * Возвращает идентификатор пользователя.
	 * @return integer
	 */
	public function GetId()
	{
		return $this->_info['id'];
	}
	
	
	/**
	 * Возвращает имя пользователя.
	 * @return string
	 */
	public function GetLogin()
	{
		return $this->_info['username'];
	}
	
	/**
	 * Проверяет, можно ли установить такой логин.
	 * @param string $value
	 * @return boolean
	 */
	public function CanSetLogin($value)
	{
		return preg_match(Env::Get()->config->Get('patterns/username'), $value) == 1;
	}
	
	
	/**
	 * Возвращает хэш пароля.
	 * @param string $password
	 * @param string $username
	 * @return string
	 */
	protected function _GenerateHash($password, $username = null)
	{
		if (empty($username))
		{
			$username = $this->GetLogin();
		}
		
		return sha1(Util_String::ToUpper($username.':'.$password));
	}
	
	/**
	 * Проверяет, установлен ли у аккаунта именно такой пароль.
	 * @param string $password
	 * @return boolean
	 */
	public function HasPassword($password)
	{
		return $this->_GenerateHash($password) == $this->_info['hash'];
	}
	
	/**
	 * Проверяет, можно ли установить такой пароль.
	 * @param string $value
	 * @return boolean
	 */
	public function CanSetPassword($value)
	{
		return preg_match(Env::Get()->config->Get('patterns/password'), $value) == 1;
	}
	
	/**
	 * Устанавливает новый пароль.
	 * @param string $value
	 */
	public function SetPassword($value)
	{
		$hash = $this->_GenerateHash($value);
		Env::Get()->db->Get('game')->Query('
			UPDATE #realm.account
			SET
				sha_pass_hash = :hash,
				v = \'\',
				s = \'\'
			WHERE id = :id
		', array(
			'hash' => array('s', $hash),
			'id' => array('d', $this->GetId()),
		));
		
		$this->_info['hash'] = $hash;
	}
	
	
	/**
	 * Возвращает адрес почты.
	 * @return string
	 */
	public function GetEmail()
	{
		return strval($this->_info['email']);
	}
	
	/**
	 * Является ли адрес почты корректным?
	 * @return boolean
	 */
	public function IsEmailCorrect()
	{
		return Util_String::IsEmail($this->GetEmail());
	}
	
	/**
	 * Возвращает слегка зашифрованный адрес почты.
	 * @return string
	 */
	public function GetMaskedEmail()
	{
		$parts = explode("@" , $this->GetEmail(), 2);
		
		//Обрабатываем имя пользователя.
		//Заменяем звездочками все символы в логине кроме первого и последнего:
		if (strlen($parts[0]) > 2)
		{
			$parts[0] = substr($parts[0], 0, 1).str_repeat('*', strlen($parts[0]) - 2).substr($parts[0], -1);
		}
		
		//Обрабатываем домен, если есть.
		//Заменяем звездочками название хоста (кроме первой буквы):
		if (isset($parts[1]) && strlen($parts[1]) > 1)
		{
			$parts[1] = substr($parts[1], 0, 1).str_repeat('*', strlen($parts[1]) - 1);
		}

		return implode('@', $parts);
	}
	
	/**
	 * Проверяет, можно ли установить такой адрес почты.
	 * @param string $value
	 */
	public function CanSetEmail($value)
	{
		return Util_String::IsEmail($value);
	}
	
	/**
	 * Устанавливает новый адрес почты.
	 * @param string $value
	 */
	public function SetEmail($value)
	{
		Env::Get()->db->Get('game')->Query('
			UPDATE #realm.account
			SET email = LOWER(:email)
			WHERE id = :id
		', array(
			'email' => array('s', $value),
			'id' => array('d', $this->GetId()),
		));
		
		$this->_info['email'] = $value;
	}

	
	/**
	 * Возвращает IP-адрес пользователя.
	 * @return string
	 */
	public function GetIp()
	{
		return Env::Get()->request->GetIp();
	}
	
	/**
	 * Возвращает уровень пользователя.
	 * @return integer
	 */
	public function GetLevel()
	{
		return $this->_info['gmlevel'];
	}
	
	
	/**
	 * Возвращает, приклеплен ли аккаунт к IP-адресу.
	 * @return bool
	 */
	public function IsLocked()
	{
		return $this->_info['locked'] == 1;
	}
	
	/**
	 * Включает/выключает блокировку аккаунта по IP-адресу.
	 * @param integer $value
	 */
	public function SetLocked($value)
	{
		$value = $value ? 1 : 0;
		
		Env::Get()->db->Get('game')->Query('
			UPDATE #realm.account
			SET locked = :locked
			WHERE id = :id
		', array(
			'locked' => array('s', $value),
			'id' => array('d', $this->GetId()),
		));
		
		$this->_info['locked'] = $value;
	}
	
	
	/**
	 * Возвращает дату регистрации.
	 * @return string
	 */
	public function GetJoinDate()
	{
		return $this->_info['joinDate'];
	}
	
	/**
	 * Возвращает время последнего входа.
	 * @return string
	 */
	public function GetLastLoginDate()
	{
		return $this->_info['lastLoginDate'];
	}
	
	
	/**
	 * Является ли пользователь обычным игроком?
	 * @return bool
	 */
	public function IsPlayer()
	{
		return ($this->_info['gmlevel'] == self::LEVEL_PLAYER);
	}

	/**
	 * Является ли пользователь модератором?
	 * @return bool
	 */
	public function IsModerator()
	{
		return ($this->_info['gmlevel'] == self::LEVEL_MODERATOR);
	}
	
	/**
	 * Является ли пользователь геймастером?
	 * @return bool
	 */
	public function IsGameMaster()
	{
		return ($this->_info['gmlevel'] == self::LEVEL_GAMEMASTER);
	}
	
	/**
	 * Является ли пользователь администратором?
	 * @return bool
	 */
	public function IsAdministrator()
	{
		return ($this->_info['gmlevel'] >= self::LEVEL_ADMINISTRATOR);
	}

	
	/**
	 * Забанен ли аккаунт?
	 * @return bool
	 */
	public function IsBanned()
	{
		return $this->_info['banned'];
	}

	/**
	 * Возвращает список банов для аккаунта.
	 * @return array
	 */
	public function GetBans()
	{
		if ($this->_bans !== null)
		{
			return $this->_bans;
		}
		
		$data = Env::Get()->db->Get('game')->Query('
			SELECT *
			FROM #realm.account_banned
			WHERE id = :id
			ORDER BY bandate DESC
		', array(
			'id' => array('d', $this->GetId())
		))->FetchAll();
		
		$result = array();
		foreach ($data as $row)
		{
			$result[] = new User_Account_Ban($row);
		}
		
		$this->_bans = $result;
		return $result;
	}
	
	
	/**
	 * Является ли авторизованным?
	 * @return bool
	 */
	public function IsGuest()
	{
		return Env::Get()->user->IsGuest();
	}


	/**
	 * Является ли аккаунт премиум-аккаунтом в данный момент?
	 */
	public function IsPremium()
	{
		if (isset($this->_info['isPremium']) == false)
		{
			//Если премиум-аккаунты включены, лезем в базу:
			if (Env::Get()->config->Get('premiumAccountPeriod'))
			{
				$count = Env::Get()->db->Get('game')->Query('
					SELECT COUNT(*)
					FROM #realm.account_premium
					WHERE TRUE
						AND id = :id
						AND active = 1
				', array(
					'id' => array('d', $this->GetId())
				))->FetchOne();
				
				$isPremium = ($count != 0);
			}
			else
			{
				$isPremium = false;
			}
			
			$this->_info['isPremium'] = $isPremium;
		}
		
		return $this->_info['isPremium'];
	}
	
	/**
	 * Делает аккаунт премиум-аккаунтом.
	 */
	public function SetPremium()
	{
		$premium = Env::Get()->config->Get('premiumAccountPeriod');
		Env::Get()->db->Get('game')->Query('
			INSERT INTO #realm.account_premium (id, setdate, unsetdate)
			VALUES (:id, :setdate, :unsetdate)
		', array(
			'id' => array('d', $this->GetId()),
			'setdate' => array('d', time()),
			'unsetdate' => array('d', time() + $premium)
		));
	}

	/**
	 * Возвращает список персонажей на аккаунте.
	 * @return User_Character_List
	 */
	public function GetCharacters()
	{
		return User_Character_List::Factory($this->GetId());
	}
	
	/**
	 * Возвращает информацию про чеки на аккаунте.
	 * @return User_Money_Cash
	 */
	public function GetCash()
	{
		if ($this->_cash === null)
		{
			$this->_cash = User_Money_Cash::Factory($this);
		}
		
		return $this->_cash;
	}
	
	/**
	 * Отправляет письмо на привязанный к аккаунту адрес.
	 * @param string $subject
	 * @param string $path
	 * @param array $vars
	 */
	public function SendMail($subject, $path, $vars = null)
	{
		//Проверяем корректность адреса:
		if ($this->IsEmailCorrect() == false)
		{
			throw new Exception_User_Account_BadEmail();
		}
		
		Util_Mail::Send($this->GetEmail(), $subject, $path, $vars);
	}
	
	/**
	 * Возвращает историю операций с аккаунтом.
	 * @return User_Account_History
	 */
	public function GetHistory()
	{
		return new User_Account_History($this->GetId());
	}
};
