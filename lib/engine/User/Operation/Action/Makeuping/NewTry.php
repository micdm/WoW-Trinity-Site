<?php

/**
 * Повторное изменение внешности без затрат золота.
 * @package User_Operation_Action_Makeuping
 * @author Mic, 2010
 */
class User_Operation_Action_Makeuping_NewTry extends User_Operation_Action_Renaming_NewTry
{
	public function GetDescription($accounts, $characters, $plain, $custom)
	{
		$character = $characters['guid'];
		return sprintf('Повторная смена внешности у персонажа %s', $character['name'], $character['guid']);
	}
	
	/**
	 * @return integer;
	 */
	protected function _GetFlag()
	{
		return User_Operation_Makeuping::GetFlag();
	}
	
	protected function _DoSomeActions()
	{
		parent::_DoSomeActions();
		$this->_SetSuccessMessages('персонаж готов к повторной смене внешности');
	}
};
