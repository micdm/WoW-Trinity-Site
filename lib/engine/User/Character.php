<?php

/**
 * Один персонаж, и все про него.
 * @package User_Character
 * @author Mic, 2010
 */
class User_Character extends ArrayType
{
	const SEARCH_BY_GUID							= 'guid';
	const SEARCH_BY_NAME							= 'name';
	
	/**
	 * Информация о персонаже.
	 * @var array
	 */
	protected $_info;
	
	/**
	 * Рейтинги персонажа.
	 * @var User_Character_Rating
	 */
	protected $_rating;
	
	/**
	 * @param string $key
	 * @return User_Character
	 */
	public static function Factory($key, $searchBy = self::SEARCH_BY_GUID)
	{
		//Пытаемся получить из хранилища:
		$result = Util_Map::Get('character/'.$searchBy.'/'.$key);
		if ($result === null)
		{
			//Загружаем из базы:
			$info = Env::Get()->db->Get('game')->Query('
				SELECT guid, account, name, race, class, level, money, logout_time AS logoutTime, online
				FROM characters
				WHERE '.$searchBy.' = :search
			', array(
				'search' => array('s', $key)
			))->FetchRow();
	
			//Помещаем в хранилище:
			$result = new self($info);
			if ($info)
			{
				Util_Map::Set('character/'.self::SEARCH_BY_GUID.'/'.$info['guid'], $result);
				Util_Map::Set('character/'.self::SEARCH_BY_NAME.'/'.$info['name'], $result);
			}
			else
			{
				//Информация про персонажа не найдена, поэтому нельзя пойти по предыдущей ветке:
				Util_Map::Set('character/'.$searchBy.'/'.$key, $result);
			}			
		}

		//Персонаж не найден:
		if ($result->_IsNotLoaded())
		{
			throw new Exception_User_Character_NotFound();
		}

		return $result;
	}
	
	/**
	 * Сбрасывает кэш загруженных персонажей.
	 * Используется в тестах.
	 */
	public static function ResetState()
	{
		Util_Map::Clear('character');
	}
	
	public function __construct($info)
	{
		$this->_info = $info;
	}
	
	public function __toString()
	{
		return $this->GetName().' ('.$this->GetGuid().')';
	}
	
	/**
	 * Персонаж не загрузился?
	 * @return bool
	 */
	private function _IsNotLoaded()
	{
		return empty($this->_info);
	}
	
	/**
	 * Возвращает уникальный идентификатор персонажа.
	 * @return integer
	 */
	public function GetGuid()
	{
		return $this->_info['guid'];
	}
	
	/**
	 * Возвращает объект аккаунта, которому принадлежит персонаж.
	 * @return User_Account
	 */
	public function GetAccount()
	{
		return Env::Get()->user->Find($this->_info['account']);
	}
	
	/**
	 * Прикрепляет персонажа к другому аккаунту.
	 * @param mixed $account объект либо просто идентификатор целевого аккаунта
	 */
	public function SetAccount($account)
	{
		$id = is_object($account) ? $account->GetId() : $account;

		Env::Get()->db->Get('game')->Query('
			UPDATE characters
			SET account = :account
			WHERE guid = :guid
		', array(
			'account' => array('d', $id),
			'guid' => array('d', $this->GetGuid())
		));
		
		$this->_info['account'] = $id;
	}
	
	/**
	 * Возвращает имя персонажа.
	 * @return string
	 */
	public function GetName()
	{
		return $this->_info['name'];
	}
	
	/**
	 * Возвращает расу персонажа.
	 * @return User_Character_Race
	 */
	public function GetRace()
	{
		return new User_Character_Race($this->_info['race']);
	}
	
	/**
	 * Возвращает класс персонажа.
	 * @return User_Character_Class
	 */
	public function GetClass()
	{
		return new User_Character_Class($this->_info['class']);
	}
	
	/**
	 * Возвращает уровень персонажа.
	 * @return integer
	 */
	public function GetLevel()
	{
		return $this->_info['level'];
	}
	
	/**
	 * Возвращает количество денег у персонажа в меди.
	 * @return integer
	 */
	public function GetMoney()
	{
		return $this->_info['money'];
	}
	
	/**
	 * Задает новое количество денег у персонажа.
	 * Параметры принимаются в медных.
	 * @param integer $delta
	 * @param integer $absolute
	 */
	public function SetMoney($delta, $absolute = null)
	{
		if (isset($absolute))
		{
			$this->_info['money'] = $absolute;
		}
		else if ($delta != 0)
		{
			$this->_info['money'] += $delta;
		}
		
		Env::Get()->db->Get('game')->Query('
			UPDATE characters
			SET money = :money
			WHERE guid = :guid
		', array(
			'money' => array('d', $this->_info['money']),
			'guid' => array('d', $this->GetGuid())
		));
	}
	
	/**
	 * Проверяет, находится ли персонаж в онлайне.
	 * @return boolean
	 */
	public function IsOnline()
	{
		return ($this->_info['online'] == 1);
	}
	
	/**
	 * Возвращает время выхода персонажа из мира.
	 * @return integer
	 */
	public function GetLogoutTime()
	{
		return $this->_info['logoutTime'];
	}
	
	/**
	 * Возвращает надетые на персонажа вещи.
	 * @return array
	 */
	public function GetEquipment()
	{
		//Загружаем, если не сделали это раньше:
		if (empty($this->_info['equipment']))
		{
			$list = Env::Get()->db->Get('game')->Query('
				SELECT slot, item_template
				FROM character_inventory
				WHERE TRUE
					AND guid = :guid
					AND bag = 0
					AND slot <= 18
			', array(
				'guid' => array('d', $this->GetGuid()),
			))->FetchAll();
			
			//Раскладываем:
			$items = array();
			foreach ($list as $item)
			{
				$items[$item['slot']] = $item['item_template'];
			}
			
			$items = World_Item::Factory($items);
			$keys = array(
				'head' => 0,
				'neck' => 1,
				'shoulders' => 2,
				'back' => 14,
				'chest' => 4,
				'body' => 3,
				'tabard' => 18,
				'wrists' => 8,
				'hands' => 9,
				'waist' => 5,
				'legs' => 6,
				'feet' => 7,
				'finger1' => 10,
				'finger2' => 11,
				'trinket1' => 12,
				'trinket2' => 13,
				'mainhand' => 15,
				'offhand' => 16,
				'ranged' => 17,
			);
			
			//Сопоставляем ключи и предметы:
			$result = array();
			foreach ($keys as $key => $i)
			{
				$result[$key] = isset($items[$i]) ? $items[$i] : null;
			}
			
			$this->_info['equipment'] = $result;
		}
		
		return $this->_info['equipment'];
	}
	
	/**
	 * Возвращает объект с рейтингами персонажа.
	 * @return User_Character_Rating
	 */
	public function GetRating()
	{
		if ($this->_rating === null)
		{
			$this->_rating = new User_Character_Rating($this->GetGuid());
		}
		
		return $this->_rating;
	}
	
	/**
	 * Активирует флаг переименования.
	 */
	public function ActivateRenaming()
	{
		if (Env::Get()->debug->IsTesting())
		{
			return;
		}
		
		Third_TrinityConsole::DoCommand('CharacterRename', $this->GetName());
	}
	
	/**
	 * Активирует флаг смены внешности.
	 */
	public function ActivateMakeuping()
	{
		if (Env::Get()->debug->IsTesting())
		{
			return;
		}
		
		Third_TrinityConsole::DoCommand('CharacterCustomize', $this->GetName());
	}
	
	/**
	 * Отправляет персонажу письмо в игру.
	 * @return User_Character_Mail
	 */
	public function CreateMail()
	{
		$mail = new User_Character_Mail();
		$mail->SetReceiver($this);
		
		return $mail;
	}
	
	/**
	 * Возвращает адрес юзербара.
	 * @return string
	 */
	public function GetUserbarUrl()
	{
		return Site_Userbar_Loader::GetUrl($this->GetGuid());
	}
};
