<?php

/**
 * Обычное изменение внешности.
 * @package User_Operation_Action_Makeuping
 * @author Mic, 2010
 */
class User_Operation_Action_Makeuping_Main extends User_Operation_Action_Renaming_Main
{
	public function GetDescription($accounts, $characters, $plain, $custom)
	{
		$character = $characters['guid'];
		return sprintf('Смена внешности у персонажа %s', $character['name']);
	}
	
	protected function _DoSomeActions()
	{
		parent::_DoSomeActions();
		$this->_SetSuccessMessages('персонаж готов к смене внешности');
	}
};
