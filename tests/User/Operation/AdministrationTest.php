<?php

/**
 * @author Mic, 2010
 */
class User_Operation_AdministrationTest extends User_Operation_BaseTest
{
	/**
	 * Запускает начисление чеков.
	 * @param integer $cheques
	 * @param integer $wmr
	 */
	protected function _SendCheques($cheques, $wmr = '')
	{
		Env::Get()->request->post = array(
			'webmoney' => true,
			'receiver' => 1,
			'cheques' => $cheques,
			'wmr' => $wmr
		);
		
		User_Operation_Base::Factory('administration')->Run(false);
	}
	
	/**
	 * Обновляет информацию для аккаунта.
	 * @param string $email
	 * @param string $password
	 */
	protected function _UpdateAccount($email = null, $password = null, &$operation = null)
	{
		Env::Get()->request->post = array(
			'account' => true,
			'username' => 'foo',
			'email' => $email,
			'password' => $password
		);
		
		$operation = User_Operation_Base::Factory('administration');
		$operation->Run(false);
	}
	
	/**
	 * Проверяем некорректное количество чеков.
	 * @expectedException Exception_User_Operation_BadCondition
	 */
	public function testIfBadCheques()
	{
		$this->_SendCheques(-1);
	}
	
	/**
	 * Чеки не указаны, а WMR указаны некорректно.
	 * @dataProvider providerIfNoChequesAndBadWmr
	 * @expectedException Exception_User_Operation_BadCondition
	 */
	public function testIfNoChequesAndBadWmr($wmr)
	{
		$this->_SendCheques(0, $wmr);
	}
	
	public static function providerIfNoChequesAndBadWmr()
	{
		return array(
			array(0),
			array(-1)
		);
	}
	
	/**
	 * Проверяем нормальное начисление, если указаны чеки.
	 * @expectedException Exception_Http_Redirected
	 */
	public function testSendChequesNormal()
	{
		try
		{
			$this->_SendCheques(10);
		}
		catch (Exception_Http_Redirected $e)
		{
			//Проверяем, что чеки пришли:
			$this->assertEquals(10, Env::Get()->user->GetCash()->Get());

			throw $e;
		}
	}
	
	/**
	 * Проверяем нормальное начисление, если указаны WMR.
	 * @expectedException Exception_Http_Redirected
	 */
	public function testSendChequesNormalIfNoCheques()
	{
		try
		{
			$this->_SendCheques(0, 20);
		}
		catch (Exception_Http_Redirected $e)
		{
			//Проверяем, что чеки пришли:
			$this->assertEquals(User_Money_Converting::FromWmr(20), Env::Get()->user->GetCash()->Get());

			throw $e;
		}
	}
	
	/**
	 * Проверяем смену почты для аккаунта, если новый адрес указан некорректно.
	 * @expectedException Exception_User_Operation_BadCondition
	 */
	public function testUpdateAccountIfBadEmail()
	{
		$this->_UpdateAccount('foo');
	}
	
	/**
	 * Проверяем смену пароля для аккаунта, если новый пароль указан некорректно.
	 * @expectedException Exception_User_Operation_BadCondition
	 */
	public function testUpdateAccountIfBadPassword()
	{
		$this->_UpdateAccount(null, '!@#$');
	}
	
	/**
	 * Проверяем поиск аккаунта без внесения изменений.
	 */
	public function testUpdateAccountIfNoChanges()
	{
		$this->_UpdateAccount(null, null, $operation);
		$this->assertEquals('foo', $operation->GetAccount()->GetLogin());
	}
	
	/**
	 * Проверяем обновление аккаунта с внесением изменений.
	 * @expectedException Exception_Http_Redirected
	 */
	public function testUpdateAccountNormal()
	{
		$email = 'not_foo@example.com';
		$password = 'newpassword';
		
		try
		{
			$this->_UpdateAccount($email, $password, $operation);
		}
		catch (Exception_Http_Redirected $e)
		{
			$account = $operation->GetAccount();
			$this->assertEquals($email, $account->GetEmail());
			$this->assertTrue($account->HasPassword($password));
			
			throw $e;
		}
	}
};
