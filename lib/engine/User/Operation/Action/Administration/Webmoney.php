<?php

/**
 * Начисление вебмани.
 * @package User_Operation_Action_Administration
 * @author Mic, 2010
 */
class User_Operation_Action_Administration_Webmoney extends User_Operation_Action_Base
{
	protected function _Setup()
	{
		$this
			->_AddAccount('receiver')
			->_AddPlainField('cheques', array('datatype' => 'float'))
			->_AddPlainField('wmr', array('datatype' => 'integer'));
	}
	
	public function GetDescription($accounts, $characters, $plain, $custom)
	{
		$account = $accounts['receiver'];
		$cheques = Util_String::GetNumber($plain['cheques'], array('чек', 'чека', 'чеков'));
		$wmr = $plain['wmr'];
		return sprintf('Начисление %s аккаунту %d за %d WMR', $cheques, $account, $wmr);
	}
	
	protected function _CheckAdditionalConditions()
	{
		//Проверяем количество чеков.
		//Если чеки не указаны, проверяем количество WMR.
		$cheques = $this->GetPlainField('cheques');
		if ($cheques < 0)
		{
			throw new Exception_User_Operation_BadCondition('укажите корректное количество чеков');
		}
		else if ($cheques == 0 && $this->GetPlainField('wmr') <= 0)
		{
			throw new Exception_User_Operation_BadCondition('укажите корректное количество WMR');
		}
	}

	protected function _DoSomeActions()
	{
		$cheques = $this->GetPlainField('cheques');
		if ($cheques == 0)
		{
			//Конвертируем в чеки:
			$cheques = User_Money_Converting::FromWmr($this->GetPlainField('wmr'));
		}

		//Пополняем баланс:
		User_Money_Cash::Factory($this->GetAccount())->Change($cheques, 'wmr_payment');
		
		$this->_SetSuccessMessages('чеки начислены');
	}
};
