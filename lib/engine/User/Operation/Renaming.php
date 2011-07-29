<?php

/**
 * Сервис переименования персонажа.
 * @package User_Operation
 * @author Mic, 2010
 */
class User_Operation_Renaming extends User_Operation_Base
{
	/**
	 * Возвращает метод, который надо вызвать у объекта персонажа для завершения операции.
	 * @return integer
	 */
	public function GetCharacterMethod()
	{
		return 'ActivateRenaming';
	}
	
	/**
	 * Возвращает название таблицы, куда будет записываться время выхода персонажа
	 * для создания второй попытки.
	 * @return string
	 */
	public function GetTableName()
	{
		return 'site_operation_renaming';
	}
	
	protected function _Setup()
	{
		$this->_AddAction('main', 'Renaming_Main');
		$this->_AddAction('newTry', 'Renaming_NewTry');
	}
	
	/**
	 * @return array
	 */
	public function GetCharacters()
	{
		return array(
			'main' => Env::Get()->user->GetAccount()->GetCharacters()->GetAll(),
			'newTry' => $this->_actions['newTry']->GetCharacters(),
		);
	}
};
