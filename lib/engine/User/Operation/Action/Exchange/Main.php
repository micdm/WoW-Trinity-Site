<?php

/**
 * Обычный обмен.
 * @package User_Operation_Action_Exchange
 * @author Mic, 2010
 */
class User_Operation_Action_Exchange_Main extends User_Operation_Action_Transfer_Main
{
	protected function _GetSubjectForMailConfirm()
	{
		return 'Обмен персонажами: новая заявка';
	}
	
	protected function _Setup()
	{
		$this
			->_SetMailConfirmRequired()
			->_AddCharacter('my', array(
				'mustBelong' => true,
				'canBeOnline' => true,
			))
			->_AddCharacter('its', array(
				'isName' => true,
				'mustNotBelong' => true,
				'canBeOnline' => true,
			));
	}
	
	public function GetDescription($accounts, $characters, $plain, $custom)
	{
		$my = $characters['my'];
		$its = $characters['its'];
		return sprintf('Создание заявки на обмен персонажами %s и %s', $my['name'], $its['name']);
	}
	
	protected function _CheckAdditionalConditions()
	{
		//Обмен с премиум-аккаунтом запрещен:
		if ($this->GetCharacter('my')->GetAccount()->IsPremium() || $this->GetCharacter('its')->GetAccount()->IsPremium())
		{
			throw new Exception_User_Operation_BadCondition('Вам нужно подождать, пока каждый из аккаунтов перестанет получать дополнительный опыт');
		}
		
		//Проверяем исходный аккаунт после обмена:
		$list = $this->_GetCharacterList($this->GetCharacter('my')->GetAccount(), $this->GetCharacter('its'), $this->GetCharacter('my'));
		try
		{
			$this->_CheckListForAnyErrors($list);
		}
		catch (Exception_User_Operation_Transfer_BadFaction $e)
		{
			throw new Exception_User_Operation_BadCondition('принимаемый персонаж не соответствует фракции Вашего аккаунта');
		}
		catch (Exception_User_Operation_Transfer_HighLevelRequiredForDk $e)
		{
			throw new Exception_User_Operation_BadCondition('Вы не можете перенести персонажа, если на Вашем аккаунте есть рыцарь смерти, но не остается других высокоуровневых персонажей');
		}
		
		//Проверяем целевой аккаунт после переноса:
		$list = $this->_GetCharacterList($this->GetCharacter('its')->GetAccount(), $this->GetCharacter('my'), $this->GetCharacter('its'));
		try
		{
			$this->_CheckListForAnyErrors($list);
		}
		catch (Exception_User_Operation_Transfer_BadFaction $e)
		{
			throw new Exception_User_Operation_BadCondition('на указанном аккаунте есть персонажи противоположной фракции');
		}
		catch (Exception_User_Operation_Transfer_HighLevelRequiredForDk $e)
		{
			$levelForDk = Env::Get()->config->Get('minLevelForDeathknight');
			throw new Exception_User_Operation_BadCondition('Вы можете перенести рыцаря смерти, только если на целевом аккаунте есть персонажи высокого уровня (выше '.$levelForDk.'');
		}		
	}

	protected function _DoSomeActions()
	{
		//Добавляем запись в таблицу обменов:
		Env::Get()->db->Get('game')->Query('
			INSERT IGNORE INTO #site.site_operation_exchange (guid_my, guid_its)
			VALUES (:my, :its)
		', array(
			'my' => array('d', $this->GetCharacter('my')->GetGuid()),
			'its' => array('d', $this->GetCharacter('its')->GetGuid()),
		));
		
		$this->_SetSuccessMessages('заявка на обмен создана');
	}
};
