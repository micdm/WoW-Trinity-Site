<?php

/**
 * Баланс пользователя.
 * @author Mic, 2010
 * @package User_Money
 */
class User_Money_Cash
{
	/**
	 * Аккаунт, с которым работаем.
	 * @var integer
	 */
	protected $_account;
	
	/**
	 * Баланс пользователя.
	 * @var float
	 */
	protected $_balance;
	
	/**
	 * @param mixed $account
	 * @return User_Money_Cash
	 */
	public static function Factory($account)
	{
		$cash = new self();
		$cash->_SetAccount($account);
		return $cash;
	}
	
	/**
	 * Загружает информацию об аккаунте.
	 * @param mixed $account
	 */
	protected function _SetAccount($account)
	{
		if ($account instanceof User_Account)
		{
			$this->_account = $account->GetId();
		}
		else
		{
			$this->_account = $account;
		}
	}
	
	/**
	 * Загружает баланс пользователя.
	 * @param bool $force
	 */
	protected function _Load($force = false)
	{
		if ($this->_balance === null || $force)
		{
			$balance = Env::Get()->db->Get('game')->Query('
				SELECT SUM(delta)
				FROM #site.site_account_cash_changes
				WHERE account = :account
			', array(
				'account' => array('d', $this->_account)
			))->FetchOne();

			$this->_balance = round($balance, 2);
		}
	}
	
	/**
	 * Возвращает баланс пользователя.
	 * @return float
	 */
	public function Get()
	{
		$this->_Load();
		return $this->_balance;
	}
	
	/**
	 * Изменяет баланс пользователя.
	 * @param float $delta
	 * @param string $reason
	 * @param mixed $extra
	 */
	public function Change($delta, $reason, $extra = null)
	{
		$this->_Load();
		
		//Проверяем на всякий случай:
		if ($this->_balance + $delta < 0)
		{
			throw new Exception_Runtime('баланс не может стать отрицательным');
		}

		//Сохраняем в базу:
		Env::Get()->db->Get('game')->Query('
			INSERT INTO #site.site_account_cash_changes (account, delta, reason, extra, ip)
			VALUES (:account, :delta, :reason, :extra, :ip)
		', array(
			'account' => array('d', $this->_account),
			'delta' => array('d', $delta),
			'reason' => array('s', $reason),
			'extra' => array('s', serialize($extra)),
			'ip' => array('s', Env::Get()->request->GetIp())
		));
		
		//Перезагружаем баланс:
		$this->_Load(true);
	}
};
