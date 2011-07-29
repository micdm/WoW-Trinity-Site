<?php

class User_Operation_BaseTest extends PHPUnit_Framework_TestCase
{
	protected $_request;
	
	protected $_user;
	
	public function setUp()
	{
		User_Account::ResetState();
		User_Character::ResetState();
		
		Env::Get()->db->Get('game')->Begin();
		
		$this->_request = Env::Get()->request;
		Env::Get()->request = new TestHelper_Request();
		
		Env::Get()->request->post = array(
			//Чтобы запустить действие:
			'main' => true
		);
		
		$this->_user = Env::Get()->user;
		Env::Get()->user = new TestHelper_User();
		
		TestHelper_Account::Add(1);
		Env::Get()->user->Authorize(1);
	}
	
	public function tearDown()
	{
		Env::Get()->request = $this->_request;
		Env::Get()->user = $this->_user;
		
		Env::Get()->db->Get('game')->Rollback();
		
		User_Account::ResetState();
		User_Character::ResetState();
	}
};
