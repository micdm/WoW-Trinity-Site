<?php

/**
 * Получение предметов за чеки.
 * @package User_Operation_Action_Donating
 * @author Mic, 2010
 */
class User_Operation_Action_Donating_Gold extends User_Operation_Action_Donating_Main
{
	protected function _Setup()
	{
		$this
			->_AddCharacter('receiver', array(
				'isName' => true,
				'canBeOnline' => true,
			))
			->_AddPlainField('amount');
	}
	
	public function GetDescription($accounts, $characters, $plain, $custom)
	{
		$receiver = $characters['receiver'];
		$amount = Util_String::GetNumber($plain['amount'], array('чека', 'чеков', 'чеков'));
		return sprintf('Обмен %s на золото персонажем %s', $amount, $receiver['name']);
	}

	protected function _CheckAdditionalConditions()
	{
		//Проверяем количество чеков:
		$this->_sum = intval($this->GetPlainField());
		if ($this->_sum <= 0)
		{
			throw new Exception_User_Operation_BadCondition('укажите корректное количество чеков');
		}
		
		$this->_CheckBalance($this->_sum);
	}
	
	protected function _DoSomeActions()
	{
		//Рассчитываем:
		$gold = User_Money_Converting::ToGold($this->_sum);
		
		//Отсылаем золото:
		$mail = $this->GetCharacter()->CreateMail();
		$mail
			->SetSubject('В благодарность')
			->SetBody('Благодарим за поддержку сервера. Желаем приятной игры!')
			->SetMoney($gold)
			->Send();
			
		//Уменьшаем баланс:
		Env::Get()->user->GetCash()->Change(-$this->_sum, 'donate_gold', array($this->GetCharacter(), $gold));
		
		$this->_SetSuccessMessages('золото отправлено по игровой почте');
	}
};
