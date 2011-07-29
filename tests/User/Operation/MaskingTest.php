<?php

/**
 * @author Mic, 2010
 */
class User_Operation_MaskingTest extends User_Operation_BaseTest
{
	/**
	 * Проверяем нормальное переключение.
	 * @expectedException Exception_Http_Redirected
	 */
	public function testNormal()
	{
		//Создаем пару персонажей:
		TestHelper_Character::Add(1, 'foo', 1);
		TestHelper_Character::Add(2, 'bar', 1);
		
		Env::Get()->request->post['list'] = array(2);
		
		try
		{
			$operation = User_Operation_Base::Factory('masking');
			$operation->Run(false);
		}
		catch (Exception_Http_Redirected $e)
		{
			//Проверяем, что изменения сохранились:
			$characters = $operation->GetCharacters();
			$this->assertEquals(false, $characters[1]['hidden']);
			$this->assertEquals(true, $characters[2]['hidden']);
			
			throw $e;
		}
	}
};
