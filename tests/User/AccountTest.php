<?php

class User_AccountTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		Env::Get()->db->Get('game')->Begin();
		
		User_Account::ResetState();
	}
	
	public function tearDown()
	{
		Env::Get()->db->Get('game')->Rollback();
		
		User_Account::ResetState();
	}

	/**
	 * Проверяем поиск аккаунта.
	 * @dataProvider providerFindAccount
	 */
	public function testFindAccount($key, $searchBy)
	{
		//Добавляем аккаунт:
		TestHelper_Account::Add(1);

		$account = User_Account::Factory($key, $searchBy);
		$this->assertEquals(1, $account->GetId());
	}
	
	public static function providerFindAccount()
	{
		return array(
			array(1, User_Account::SEARCH_BY_ID),
			array('foo', User_Account::SEARCH_BY_USERNAME),
		);
	}
	
	/**
	 * Проверяем ситуацию, когда аккаунт не существует.
	 * @expectedException Exception_User_Account_NotFound
	 */
	public function testIfAccountDoesntExist()
	{
		User_Account::Factory(1);
	}
	
	/**
	 * Проверяем поиск аккаунта несколько раз:
	 * должен возвращаться один и тот же объект.
	 * @dataProvider providerIfFindAccountTwoTimes
	 */
	public function testIfFindAccountTwoTimes($key, $searchBy)
	{
		TestHelper_Account::Add(1);
		
		//Создаем два объекта для одного и того же аккаунта:
		$account1 = User_Account::Factory(1);
		$account1->SetLocked(0);
		
		$account2 = User_Account::Factory($key, $searchBy);
		$account2->SetLocked(0);
		
		//Теперь изменяем первый аккаунт:
		$account1->SetLocked(1);
		$this->assertEquals($account2->IsLocked(), $account1->IsLocked());
	}
	
	public static function providerIfFindAccountTwoTimes()
	{
		return array(
			array(1, User_Account::SEARCH_BY_ID),
			array('foo', User_Account::SEARCH_BY_USERNAME),
		);
	}
	
	/**
	 * Проверяем генерацию закрытого звездочками адреса почты.
	 * @dataProvider providerGetMaskedEmail
	 */
	public function testGetMaskedEmail($email, $expected)
	{
		$account = TestHelper_Account::Add(1);
		$account->SetEmail($email);
		$this->assertEquals($expected, $account->GetMaskedEmail());
	}
	
	public static function providerGetMaskedEmail()
	{
		return array(
			array('', ''),
			array('f', 'f'),
			array('fo', 'fo'),
			array('foo', 'f*o'),
			array('foo@', 'f*o@'),
			array('foo@b', 'f*o@b'),
			array('foo@ba', 'f*o@b*'),
		);
	}
	
	/**
	 * Проверяем создание аккаунта.
	 */
	public function testCreate()
	{
		$login = 'login';
		$password = 'password';
		$email = 'email';
		
		$account = Env::Get()->user->GetAccount()->Create($login, $password, $email);
		$this->assertEquals($login, $account->GetLogin());
		$this->assertTrue($account->HasPassword($password));
		$this->assertEquals($email, $account->GetEmail());
		$this->assertEquals(Env::Get()->request->GetIp(), $account->GetIp());
	}
};
