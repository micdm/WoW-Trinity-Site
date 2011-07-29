<?php

/**
 * Регистрация нового пользователя.
 * @package User_Operation
 * @author Mic, 2010
 */
class User_Operation_Registration extends User_Operation_Base
{
	/**
	 * Идентификатор персонажа, к которому будет привязан новый аккаунт в качестве реферала.
	 * @var integer
	 */
	protected $_referrer;
	
	public function Run($needWrapException = true)
	{
		$this->_CheckReferral();

		parent::Run($needWrapException);
	}

	/**
	 * Проверяем, указан ли реферальный аккаунт.
	 */
	protected function _CheckReferral()
	{
		$id = null;
		if (Env::Get()->request->Get('ref'))
		{
			//Если идентификатор найден в GET, выставляем куку, чтоб не потерялся:
			$id = intval(Env::Get()->request->Get('ref'));
			Util_Cookie::Set('ref', $id, time() + 3600 * 24 * 7);
		}
		else if (isset($_COOKIE['ref']))
		{
			//Еще ищем в куках:
			$id = intval($_COOKIE['ref']);
		}
		
		$this->_referrer = $id;
	}
	
	/**
	 * Возвращает объект персонажа-хозяина.
	 * @return User_Character
	 */
	public function GetReferrer()
	{
		return Env::Get()->user->GetAccount()->GetCharacters()->Find($this->_referrer);
	}
	
	/**
	 * Проверяет, нужно ли запросить у пользователя капчу.
	 * @return bool
	 */
	public function NeedCaptcha()
	{
		$count = Env::Get()->db->Get('game')->Query('
			SELECT COUNT(*)
			FROM #realm.account
			WHERE TRUE
				AND last_ip = :ip
				AND UNIX_TIMESTAMP() - UNIX_TIMESTAMP(joindate) < :time
		', array(
			'ip' => array('s', Env::Get()->request->GetIp()),
			'time' => array('d', Env::Get()->config->Get('periodBetweenRegistrations')),
		))->FetchOne();
		
		return $count != 0;
	}
};
