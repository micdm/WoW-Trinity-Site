<?php

/**
 * Получение предметов за чеки.
 * @package User_Operation_Action_Donating
 * @author Mic, 2010
 */
class User_Operation_Action_Donating_Main extends User_Operation_Action_Base
{
	/**
	 * Список идентификаторов предметов.
	 * @var array
	 */
	protected $_items;
	
	/**
	 * Общая стоимость предметов.
	 * @var integer
	 */
	protected $_sum;
	
	protected function _Setup()
	{
		$this
			->_AddCharacter('receiver', array(
				'isName' => true,
				'canBeOnline' => true,
			))
			->_AddPlainField('items');
	}
	
	public function GetDescription($accounts, $characters, $plain, $custom)
	{
		$receiver = $characters['receiver'];
		return sprintf('Обмен чеков на предметы персонажем %s', $receiver['name']);
	}

	protected function _CheckAdditionalConditions()
	{
		//Фильтруем список предметов:
		$this->_items = array_unique(array_map('intval', $this->GetPlainField()));
		if (empty($this->_items))
		{
			throw new Exception_User_Operation_BadCondition('выберите нужные предметы');
		}
		
		//Забираем цену предмeтов из базы:
		$info = Env::Get()->db->Get('game')->Query('
			SELECT sdg.price
			FROM #world.item_template AS it
				INNER JOIN #site.site_donate_goods AS sdg ON(sdg.entry = it.entry)
			WHERE TRUE
				AND it.entry IN('.implode(',', $this->_items).')
				AND sdg.is_active = TRUE
		')->FetchColumn();
		
		if (count($this->_items) != count($info))
		{
			throw new Exception_User_Operation_BadCondition('выбранные Вами предметы не существуют, попробуйте взять другие');
		}
		
		//Подсчитываем сумму:
		$this->_sum = 0;
		foreach ($info as $price)
		{
			$this->_sum += $price;
		}
		
		//Проверяем баланс:
		$this->_CheckBalance($this->_sum);
	}
	
	protected function _DoSomeActions()
	{
		//Обновляем баланс:
		Env::Get()->user->GetCash()->Change(-$this->_sum, 'donate_items', array($this->GetCharacter(), $this->_items));
		
		//Отсылаем предметы:
		$mail = $this->GetCharacter()->CreateMail();
		$mail
			->SetSubject('В благодарность')
			->SetBody('Благодарим за поддержку сервера. Желаем приятной игры!')
			->SetItems($this->_items)
			->Send();
			
		$this->_SetSuccessMessages('предметы отправлены по игровой почте');
	}
	
	/**
	 * Проверяет баланс текущего пользователя.
	 */
	protected function _CheckBalance($amount)
	{
		if (Env::Get()->user->GetCash()->Get() < $amount)
		{
			throw new Exception_User_Operation_BadCondition('у Вас недостаточно чеков');
		}
	}
};
