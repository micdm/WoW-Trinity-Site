<?php

/**
 * Сервис смены внешности персонажа.
 * @package User_Operation
 * @author Mic, 2010
 */
class User_Operation_Makeuping extends User_Operation_Renaming
{
	public function GetCharacterMethod()
	{
		return 'ActivateMakeuping';
	}
	
	public function GetTableName()
	{
		return 'site_operation_makeuping';
	}
	
	protected function _Setup()
	{
		$this->_AddAction('main', 'Makeuping_Main');
		$this->_AddAction('newTry', 'Makeuping_NewTry');
	}
};
