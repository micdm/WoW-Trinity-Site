<?php

/**
 * Повторное переименование без затрат золота.
 * @package User_Operation_Action_Renaming
 * @author Mic, 2010
 */
class User_Operation_Action_Renaming_NewTry extends User_Operation_Action_Base
{
	protected function _Setup()
	{
		$this->_AddCharacter('guid', array('mustBelong' => true));
	}
	
	public function GetDescription($accounts, $characters, $plain, $custom)
	{
		$character = $characters['guid'];
		return sprintf('Повторное пореименование персонажа %s', $character['name']);
	}
	
	protected function _CheckAdditionalConditions()
	{
		//Проверяем, заходил ли персонаж в мир после выставления флага:
		$logout = Env::Get()->db->Get('game')->Query('
			SELECT
				scn.logout_time
			FROM #site.'.$this->_operation->GetTableName().' AS scn
			WHERE TRUE
				AND scn.guid = :guid
		', array(
			'guid' => array('d', $this->GetCharacter()->GetGuid())
		))->FetchOne();
		
		if (empty($logout) || $logout != $this->GetCharacter()->GetLogoutTime())
		{
			throw new Exception_User_Operation_BadCondition('персонаж заходил в мир после операции');
		}
	}
	
	protected function _DoSomeActions()
	{
		//Выставляем флаг переименования:
		call_user_func(array($this->GetCharacter(), $this->_operation->GetCharacterMethod()));
		
		$this->_SetSuccessMessages('персонаж готов к повторному переименованию');
	}
	
	/**
	 * @return array
	 */
	public function GetCharacters()
	{
		$result = array();
		
		$list = Env::Get()->user->GetAccount()->GetCharacters();
		if ($list->GetGuids())
		{
			//Выбираем информацию для персонажей с аккаунта:
			$rows = Env::Get()->db->Get('game')->Query('
				SELECT
					scn.guid,
					scn.logout_time AS time
				FROM #site.'.$this->_operation->GetTableName().' AS scn
				WHERE TRUE
					AND scn.guid IN('.implode(',', $list->GetGuids()).')
			')->FetchAll();
			
			//Перебираем найденные результаты:
			foreach ($rows as $row)
			{
				$character = $list->Get($row['guid']);
				if ($row['time'] == $character->GetLogoutTime())
				{
					$result[$character->GetGuid()] = $character->GetName();
				}
			}
		}
		
		return $result;
	}
};
