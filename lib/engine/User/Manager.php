<?php

/**
 * Работа с пользователем.
 * @package User
 * @author Mic, 2010
 */
class User_Manager
{
	/**
	 * Информация об аккаунте.
	 * @var User_Account
	 */
	protected $_account;
	
	/**
	 * Авторизован ли пользователь?
	 * @var boolean
	 */
	protected $_isGuest;
	
	/**
	 * Баланс и работа с деньгами.
	 * @var User_Money_Cash
	 */
	protected $_cash;
	
	/**
	 * @return User_Account
	 */
	public function GetAccount()
	{
		if ($this->_account === null)
		{
			self::_LoadPersonalInfo();
		}
		
		return $this->_account;
	}
	
	/**
	 * Возвращает баланс пользователя.
	 * @return User_Money_Cash
	 */
	public function GetCash()
	{
		if ($this->_cash === null)
		{
			self::_LoadCashInfo();
		}
		
		return $this->_cash;
	}
	
	/**
	 * Загружает информацию о пользователе.
	 */
	protected function _LoadPersonalInfo()
	{
		//Выгружаем из сессии данные:
		$this->_account = Env::Get()->session->Get('user');
		
		//Если в сессии ничего нету, загружаем пустой аккаунт:
		if (empty($this->_account))
		{
			$this->_account = User_Account::Factory();
		}
		
		$this->_isGuest = (Env::Get()->session->Get('isGuest') !== false);
	}
	
	/**
	 * Сохраняет информацию о пользователе в сессии.
	 */
	protected function _StorePersonalInfo()
	{
		Env::Get()->session
			->Set('user', $this->GetAccount())
			->Set('isGuest', $this->IsGuest());
	}
	
	/**
	 * Создает объект для работы с деньгами.
	 */
	protected function _LoadCashInfo()
	{
		$this->_cash = User_Money_Cash::Factory($this->GetAccount());
	}
	
	/**
	 * Делает текущего пользователя авторизованным.
	 * @param integer $id
	 */
	public function Authorize($id)
	{
		$this->_account = User_Account::Factory($id);
		$this->_isGuest = false;

		//Сохраняем:
		self::_StorePersonalInfo();
	}
	
	/**
	 * Делает текущего пользователя неавторизованным.
	 */
	public function Deauthorize()
	{
		$this->_account = User_Account::Factory();
		$this->_isGuest = true;
		
		//Сохраняем:
		self::_StorePersonalInfo();
	}
	
	/**
	 * Перечитывает данные пользователя.
	 */
	public function Reauthorize()
	{
		if ($this->IsGuest() == false)
		{
			self::Authorize($this->GetAccount()->GetId());
		}
	}
	
	/**
	 * Авторизован ли пользователь?
	 * @return boolean
	 */
	public function IsGuest()
	{
		if ($this->_isGuest === null)
		{
			$this->_LoadPersonalInfo();
		}
		
		return $this->_isGuest;
	}
	
	/**
	 * Ищет информацию об аккаунте.
	 * @param string $id
	 * @param string $searchBy
	 * @return User_Account
	 */
	protected function _Find($id, $searchBy)
	{
		try
		{
			$account = User_Account::Factory($id, $searchBy);
		}
		catch (Exception_User_Account_NotFound $e)
		{
			$account = null;
		}
		
		return $account;
	}
	
	/**
	 * Ищет информацию об аккаунте по идентификатору.
	 * @param string $id
	 * @return User_Account
	 */
	public function Find($id)
	{
		return $this->_Find($id, User_Account::SEARCH_BY_ID);
	}
	
	/**
	 * Ищет информацию об аккаунте по логину.
	 * @param string $name
	 * @return User_Account
	 */
	public function FindByName($name)
	{
		return $this->_Find($name, User_Account::SEARCH_BY_USERNAME);
	}
	
	/**
	 * Ищет информацию об аккаунтах по IP-адресу.
	 * @param string $ip
	 * @return array
	 */
	public function FindByIp($ip)
	{
		return User_Account::FindByIp($ip);
	}
};
