<?php

/**
 * Принятие заявки на обмен.
 * @package User_Operation_Action_Exchange
 * @author Mic, 2010
 */
class User_Operation_Action_Exchange_Accept extends User_Operation_Action_Transfer_Main
{
	protected function _Setup()
	{
		//Тут нет ошибки с mustBelong/mustNotBelong, потому что в данном случае
		//в качестве "my" (то есть того, кто отправил заявку) выступает чужой персонаж.
		$this
			->_SetMailConfirmRequired()
			->_AddCharacter('my', array('mustNotBelong' => true))
			->_AddCharacter('its', array('mustBelong' => true));
	}
	
	public function GetDescription($accounts, $characters, $plain, $custom)
	{
		$my = $characters['my'];
		$its = $characters['its'];
		return sprintf('Подтверждение заявки на обмен персонажами %s и %s', $my['name'], $its['name']);
	}
	
	protected function _CheckAdditionalConditions()
	{
		//Получаем информацию про заявку:
		$request = Env::Get()->db->Get('game')->Query('
			SELECT id
			FROM #site.site_operation_exchange
			WHERE TRUE
				AND guid_my = :guidMy
				AND guid_its = :guidIts
		', array(
			'guidMy' => array('d', $this->GetCharacter('my')->GetGuid()),
			'guidIts' => array('d', $this->GetCharacter('its')->GetGuid()),
		))->FetchRow();
		
		if (empty($request))
		{
			throw new Exception_User_Operation_BadCondition('заявка не найдена, попробуйте создать новую');
		}
	}
	
	protected function _DoSomeActions()
	{
		$my = $this->GetCharacter('my');
		$its = $this->GetCharacter('its');
		
		//Меняем персонажей аккаунтами:
		$temp = $my->GetAccount();
		$my->SetAccount($its->GetAccount());
		$its->SetAccount($temp);
		
		//Удаляем все заявки на обмен для данных персонажей:
		Env::Get()->db->Get('game')->Query('
			DELETE FROM #site.site_operation_exchange
			WHERE FALSE
				OR guid_my IN(:guidMy, :guidIts)
				OR guid_its IN(:guidMy, :guidIts)
		', array(
			'guidMy' => array('d', $my->GetGuid()),
			'guidIts' => array('d', $its->GetGuid()),
		));
		
		//Помечаем обоих персонажей как перенесенных:
		$this->_AddToCompleteTransfers($my);
		$this->_AddToCompleteTransfers($its);
		
		$this->_SetSuccessMessages('обмен совершен');
	}
};
