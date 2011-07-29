<?php

/**
 * Поиск по аккаунтам, персонажам, IP-адресам.
 * @package User_Operation_Adm
 * @author Mic, 2010
 */
class User_Operation_Adm_Search extends User_Operation_Base
{
	protected function _IsUsingGetMethod()
	{
		return true;
	}
	
	protected function _Setup()
	{
		$this->_AddAction('main', 'Search');
	}
	
	/**
	 * Возвращает результаты поиска.
	 * @return array
	 */
	public function GetResults()
	{
		return $this->_actions['main']->GetResults();
	}
};
