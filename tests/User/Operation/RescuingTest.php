<?php

/**
 * @author Mic, 2010
 */
class User_Operation_RescuingTest extends User_Operation_BaseTest
{
	/**
	 * Проверяем обычное вытаскивание персонажа.
	 * @expectedException Exception_Http_Redirected
	 */
	public function testNormal()
	{
		TestHelper_Character::Add(1, 'foo', 1);
		Env::Get()->request->post['guid'] = 1;
		
		User_Operation_Base::Factory('rescuing')->Run(false);
	}
};
