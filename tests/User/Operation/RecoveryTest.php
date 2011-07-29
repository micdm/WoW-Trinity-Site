<?php

/**
 * @author Mic, 2010
 */
class User_Operation_RecoveryTest extends User_Operation_BaseTest
{
	/**
	 * Проверяем нормальное восстановление пароля.
	 * @expectedException Exception_Http_Redirected
	 */
	public function testNormal()
	{
		Env::Get()->request->post['username'] = 'foo';
		
		try
		{
			User_Operation_Base::Factory('TestOperation')->Run(false);
		}
		catch (Exception_Http_Redirected $e)
		{
			$this->assertFalse(Env::Get()->user->Find(1)->HasPassword('bar'));
			throw $e;
		}
	}
};


class User_Operation_TestOperation extends User_Operation_Recovery
{
	protected function _Setup()
	{
		$this->_AddAction('main', 'TestActionNormal');
	}
};

class User_Operation_Action_TestActionNormal extends User_Operation_Action_Recovery_Main
{
	protected function _Setup()
	{
		parent::_Setup();

		$this->_isMailConfirmRequired = false;
	}
};
