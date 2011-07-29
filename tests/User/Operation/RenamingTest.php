<?php

/**
 * @package User_OperationTest
 * @author Mic, 2010
 */
class User_Operation_RenamingTest extends User_Operation_BaseTest
{
	/**
	 * @return string
	 */
	protected function _GetOperationName()
	{
		return 'renaming';
	}
	
	/**
	 * Тестируем нормальное поведение.
	 * @expectedException Exception_Http_Redirected
	 */
	public function testNormal()
	{
		Env::Get()->user->GetCash()->Change(999999, 'test_reason');
		
		TestHelper_Character::Add(1, 'foo', 1, array('online' => 0));
		Env::Get()->request->post['guid'] = 1;

		User_Operation_Base::Factory($this->_GetOperationName())->Run(false);
	}
};
