<?php

/**
 * Перевод чеков на другой аккаунт.
 * @package User_Operation_Action_Donating
 * @author Mic, 2010
 */
class User_Operation_Action_Donating_Transfer extends User_Operation_Action_Donating_Main
{
	/**
	 * Максимум знаков после запятой.
	 * @var integer
	 */
	const MAX_CHARACTERS_AFTER_POINT				= 2;
	
	protected function _Setup()
	{
		$this
			->_AddCharacter('character', array(
				'isName' => true,
				'canBeOnline' => true,
				'canBeEmpty' => true,
				'mustNotBelong' => true,
			))
			->_AddAccount('account', array(
				'isName' => true,
				'canBeEmpty' => true,
				'mustDiffer' => true,
			))
			->_AddPlainField('amount', array('datatype' => 'float'));
	}
	
	public function GetDescription($accounts, $characters, $plain, $custom)
	{
		if ($accounts['account'])
		{
			$receiver = sprintf('аккаунту %d', $accounts['account']);
		}
		else
		{
			$character = $characters['character'];
			$receiver = sprintf('персонажу %s', $character['name']);
		}
		
		$amount = Util_String::GetNumber(round($plain['amount']), array('чека', 'чеков', 'чеков'));
		return sprintf('Перевод %s %s', $amount, $receiver);
	}

	protected function _CheckAdditionalConditions()
	{
		//Проверяем, что хоть какой-то получатель указан:
		if ($this->GetCharacter() == null && $this->GetAccount() == null)
		{
			throw new Exception_User_Operation_BadCondition('укажите получателя');
		}
		
		//Проверяем количество чеков:
		$amount = $this->GetPlainField();
		if ($amount <= 0)
		{
			throw new Exception_User_Operation_BadCondition('укажите корректное количество чеков');
		}
		
		//Разрешаем не больше двух знаков после запятой:
		$right = strstr($amount, '.');
		if ($right && strlen($right) > self::MAX_CHARACTERS_AFTER_POINT + 1)
		{
			throw new Exception_User_Operation_BadCondition('укажите дробь максимум с двумя знаками после запятой');
		}
		
		//Проверяем баланс:
		$this->_CheckBalance($amount);
	}
	
	protected function _DoSomeActions()
	{
		//Отправитель и получатель:
		$sender = Env::Get()->user->GetAccount();
		$receiver = $this->GetAccount() ? $this->GetAccount() : $this->GetCharacter()->GetAccount();
		
		//Уменьшаем баланс у одного, увеличиваем у другого:
		$amount = $this->GetPlainField();
		$sender->GetCash()->Change(-$amount, 'transfer_out', array($receiver->GetId()));
		$receiver->GetCash()->Change($amount, 'transfer_in', array($sender->GetId()));
		
		$this->_SetSuccessMessages('чеки переведены');
	}
};
