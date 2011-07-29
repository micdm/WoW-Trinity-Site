<?php

/**
 * Пополнение баланса через SMS.
 * @package User_Donating
 * @author Mic, 2010
 */
class User_Donating_Sms
{
	/**
	 * Номер аккаунта, которому начисляем чеки.
	 * @var integer
	 */
	private static $_account;
	
	/**
	 * Проверяет подпись сообщения.
	 */
	private static function _CheckHash()
	{
		//Формируем подпись:
		$sign = array();
		foreach (array('operator', 'phone', 'num', 'smsid') as $key)
		{
			$sign[] = Env::Get()->request->Get($key);
		}
		
		$sign[] = Env::Get()->config->Get('sms/key');
		
		//Бросаем исключение, если подписи не совпала:
		if (md5(implode('', $sign)) !== Env::Get()->request->Get('skey'))
		{
			throw new Exception_Runtime('подписи не совпали');
		}
	}
	
	/**
	 * Выделяет из сообщения номер аккаунта и проверяет его.
	 */
	private static function _CheckAccount()
	{
		//Обрабатываем сообщение, пытаемся найти внутри идентификатор аккаунта:
		$account = intval(trim(Env::Get()->request->Get('msg')));
		if ($account <= 0)
		{
			throw new Exception_Donate_Sms('неправильный формат сообщения');
		}

		//Ищем аккаунт в базе:
		$found = Env::Get()->db->Get('game')->Query('
			SELECT COUNT(id)
			FROM #realm.account
			WHERE id = :account
		', array(
			'account' => array('d', $account)
		))->FetchOne();
		
		if (empty($found))
		{
			throw new Exception_Donate_Sms('аккаунт не найден');
		}
		
		self::$_account = $account;
	}
	
	/**
	 * Начисляет чеки.
	 */
	private static function _ChangeBalance()
	{
		$amount = User_Money_Converting::FromSms(floor(Env::Get()->request->Get('dollarcost')));
		User_Money_Cash::Factory(self::$_account)->Change($amount, 'sms_payment');
	}
	
	public static function Run()
	{
		self::_CheckHash();
		self::_CheckAccount();
		self::_ChangeBalance();
	}
	
	/**
	 * Возвращает префикс для смсок.
	 * @return string
	 */
	public static function GetPrefix()
	{
		return Env::Get()->config->Get('sms/prefix');
	}
	
	/**
	 * Возвращает список коротких номеров и цены.
	 * @return array
	 */
	public static function GetNumbers()
	{
		return array(
			'8055' => '1',
			'8155' => '2',
			'8355' => '3',
			'7132' => '5',
			'7122' => '10'
		);
	}
};
