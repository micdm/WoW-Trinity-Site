<?php

/**
 * Поиск персонажа.
 * @package User_Operation_Action_Searching
 * @author Mic, 2010
 */
class User_Operation_Action_Searching_Main extends User_Operation_Action_Base
{
	protected function _Setup()
	{
		$this->_AddCharacter('name', array(
			'isName' => true,
			'canBeOnline' => true,
			'canBeBanned' => true,
		));
	}
	
	protected function _IsHistoryNeeded()
	{
		return false;
	}

	protected function _DoSomeActions()
	{
		$url = '/server/character/'.$this->GetCharacter()->GetGuid().'/';
		throw new Exception_Http_Redirected($url);
	}
};
