<?php

/**
 * Поиск.
 * @package User_Operation_Adm_Action_Search
 * @author Mic, 2010
 */
class User_Operation_Adm_Action_Search extends User_Operation_Action_Base
{
	/**
	 * Результаты поиска.
	 * @var array
	 */
	protected $_results = array();
	
	protected function _IsHistoryNeeded()
	{
		return false;
	}
	
	protected function _Setup()
	{
		$this->_AddAccount('account', array(
			'isName' => true,
			'canBeEmpty' => true,
			'canBeBanned' => true,
		));

		$this->_AddCharacter('character', array(
			'isName' => true,
			'canBeEmpty' => true,
			'canBeOnline' => true,
			'canBeBanned' => true,
		));
		
		$this
			->_AddPlainField('ip')
			->_SetRedirectNotRequired();
	}
	
	protected function _CheckAdditionalConditions()
	{

	}

	protected function _DoSomeActions()
	{
		if ($this->GetAccount())
		{
			//Искали аккаунт:
			$this->_results[] = $this->GetAccount();
		}
		else if ($this->GetCharacter())
		{
			//Искали персонажа:
			$this->_results[] = $this->GetCharacter()->GetAccount();
		}
		else if ($this->GetPlainField())
		{
			//Искали по IP:
			$this->_results = Env::Get()->user->FindByIp($this->GetPlainField());
		}
		
		if (empty($this->_results))
		{
			throw new Exception_User_Operation_BadCondition('ничего не найдено');
		}
	}
	
	/**
	 * Возвращает результаты поиска.
	 * @return array
	 */
	public function GetResults()
	{
		return $this->_results;
	}
};
