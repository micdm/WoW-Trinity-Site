<?php

class TestHelper_User extends User_Manager
{
	public $account;
	
	public function GetAccount()
	{
		return $this->account;
	}
	
	public function Authorize($id)
	{
		parent::Authorize($id);
		
		$this->account = parent::GetAccount();
	}
};
