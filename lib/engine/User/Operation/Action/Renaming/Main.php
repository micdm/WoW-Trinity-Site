<?php

/**
 * Обычное переименование.
 * @package User_Operation_Action_Renaming
 * @author Mic, 2010
 */
class User_Operation_Action_Renaming_Main extends User_Operation_Action_Base
{
	protected function _Setup()
	{
		$this->_AddCharacter('guid', array('mustBelong' => true));
	}
	
	public function GetDescription($accounts, $characters, $plain, $custom)
	{
		$character = $characters['guid'];
		return sprintf('Переименование персонажа %s', $character['name']);
	}
	
	protected function _DoSomeActions()
	{
		$character = $this->GetCharacter();
		
		//Выставляем флаг переименования:
		call_user_func(array($character, $this->_operation->GetCharacterMethod()));
		
		//Сохраняем время выхода персонажа из мира, чтобы иметь возможность
		//выставить флаг повторно без взимания золота:
		Env::Get()->db->Get('game')->Query('
			REPLACE INTO #site.'.$this->_operation->GetTableName().' (guid, logout_time)
			VALUES (:guid, :logout)
		', array(
			'guid' => array('d', $character->GetGuid()),
			'logout' => array('d', $character->GetLogoutTime())
		));
		
		$this->_SetSuccessMessages('персонаж готов к переименованию');
	}
};
