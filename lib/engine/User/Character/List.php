<?php

/**
 * Список персонажей.
 * @package User_Character
 * @author Mic, 2010
 */
class User_Character_List implements Countable, Iterator
{
	/**
	 * Аккаунт, с персонажами которого работаем.
	 * @var User_Account
	 */
	protected $_account;
	
	/**
	 * Список персонажей.
	 * @var array
	 */
	protected $_characters;
	
	/**
	 * @param mixed $account
	 * @return User_Character_List
	 */
	public static function Factory($account)
	{
		return new self($account);
	}
	
	/**
	 * 
	 * @param mixed $account
	 */
	public function __construct($account)
	{
		//Сохраняем информацию об аккаунте:
		if ($account instanceof User_Account)
		{
			$this->_account = $account;
		}
		else
		{
			$this->_account = User_Account::Factory($account);
		}
		
		//Загружаем персонажей:
		$this->_characters = $this->GetAll();

	}
	
	public function count()
	{
		return count($this->_characters);
	}

	function rewind()
	{
		reset($this->_characters);
	}

	function current()
	{
		return current($this->_characters);
	}

	function key()
	{
		return key($this->_characters);
	}

	function next()
	{
		next($this->_characters);
	}

	function valid()
	{
		return current($this->_characters) !== false;
    }

	/**
	 * Возвращает информацию о персонаже на данном аккаунте.
	 * @param integer $guid
	 * @return User_Character
	 */
	public function Get($guid)
	{
		$character = User_Character::Factory($guid);
		
		//Проверяем, что персонаж принадлежит аккаунту:
		$account = $character->GetAccount();
		if (empty($account) || $account->GetId() != $this->_account->GetId())
		{
			throw new Exception_User_Character_OnAnotherAccount();
		}

		return $character;
	}
	
	/**
	 * Возвращает всех персонажей на аккаунте.
	 * @return array
	 */
	public function GetAll()
	{
		//Возвращаем загруженное, если есть:
		if ($this->_characters !== null) {
			return $this->_characters;
		}
		
		if ($this->_account->GetId() == 0) {
			return array();
		}
		
		$list = Env::Get()->db->Get('game')->Query('
			SELECT guid
			FROM characters
			WHERE account = :account
			ORDER BY guid
		', array(
			'account' => array('d', $this->_account->GetId())
		))->FetchColumn();
		
		$result = array();
		foreach ($list as $guid)
		{
			$result[] = $this->Get($guid);
		}

		return $result;
	}
	
	/**
	 * Возвращает массив идентификаторов персонажей.
	 * @return array
	 */
	public function GetGuids()
	{
		$apply = create_function('$character', 'return $character->GetGuid();');
		return array_map($apply, $this->GetAll());
	}
	
	/**
	 * Проверяет, принадлежит ли персонаж аккаунту.
	 * @param integer $guid
	 */
	public function Has($guid)
	{
		try
		{
			$this->Get($guid);
		}
		catch (Exception_User_Character_OnAnotherAccount $e)
		{
			return false;
		}
		
		return true;
	}
	
	/**
	 * Ищет персонажа.
	 * @param string $key
	 * @param string $searchBy
	 */
	protected function _Find($key, $searchBy)
	{
		try
		{
			$character = User_Character::Factory($key, $searchBy);
		}
		catch (Exception_User_Character_NotFound $e)
		{
			$character = null;
		}
		
		return $character;
	}
	
	/**
	 * Ищет персонажа по идентификатору и возвращает информацию о нем.
	 * @param integer $guid
	 * @return User_Character
	 */
	public function Find($guid)
	{
		return $this->_Find(intval($guid), User_Character::SEARCH_BY_GUID);
	}
	
	/**
	 * Ищет персонажа по имени.
	 * @param string $name
	 * @return User_Character
	 */
	public function FindByName($name)
	{
		return $this->_Find($name, User_Character::SEARCH_BY_NAME);
	}
	
	/**
	 * Есть ли рыцарь смерти в списке?
	 * @return bool
	 */
	public function HasDeathknight()
	{
		foreach ($this->GetAll() as $character)
		{
			if ($character->GetClass()->IsDeathknight())
			{
				return true;
			}
		}
		
		return false;
	}
};
