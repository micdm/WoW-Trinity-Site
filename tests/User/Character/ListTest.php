<?php

class User_Character_ListTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		Env::Get()->db->Get('game')->Begin();
		
		User_Account::ResetState();
		User_Character::ResetState();
	}
	
	public function tearDown()
	{
		Env::Get()->db->Get('game')->Rollback();
		
		User_Account::ResetState();
		User_Character::ResetState();
	}
	
	public function getForGetAll()
	{
		return array(
			array(array()),
			array(array(1)),
			array(array(1,2))
		);
	}
	
	/**
	 * Тестируем ситуацию, когда персонаж находится на аккаунте.
	 */
	public function testIfCharacterOnAccount()
	{
		TestHelper_Account::Add(1);
		TestHelper_Character::Add(1, 'foo', 1);
		
		//Забираем инфо:
		$character = User_Character_List::Factory(1)->Get(1);
		$this->assertFalse(empty($character));
	}
	
	/**
	 * Персонаж находится на несуществующем аккаунте.
	 * @expectedException Exception_User_Character_OnAnotherAccount
	 */
	public function testIfCharacterOnBadAccount()
	{
		TestHelper_Account::Add(1, 'foo');
		TestHelper_Account::Add(2, 'bar');
		TestHelper_Character::Add(1, 'foo', 2);
		
		User_Character_List::Factory(1)->Get(1);
	}
	
	/**
	 * Тестируем ситуацию, когда персонаж находится на другом аккаунте.
	 * @expectedException Exception_User_Character_OnAnotherAccount
	 */
	public function testIfCharacterOnAnotherAccount()
	{
		TestHelper_Account::Add(1, 'foo');
		TestHelper_Account::Add(2, 'bar');
		TestHelper_Character::Add(1, 'foo', 2);
		
		User_Character_List::Factory(1)->Get(1);
	}
	
	/**
	 * Тестируем метод для выборки всех персонажей.
	 * @dataProvider getForGetAll
	 */
	public function testGetAll($guids)
	{
		TestHelper_Account::Add(1);
		foreach ($guids as $guid)
		{
			TestHelper_Character::Add($guid, 'foo'.$guid, 1);
		}

		$list = User_Character_List::Factory(1)->GetAll();
		
		$this->assertEquals(count($guids), count($list));
		foreach ($guids as $i => $guid)
		{
			$this->assertEquals($guid, $list[$i]->GetGuid());
		}
	}
	
	/**
	 * Тестируем метод для проверки, принадлежит ли персонаж аккаунту.
	 */
	public function testHas()
	{
		TestHelper_Account::Add(1, 'foo');
		TestHelper_Account::Add(2, 'bar');
		TestHelper_Character::Add(1, 'foo', 1);
		TestHelper_Character::Add(2, 'bar', 2);
		
		$this->assertTrue(User_Character_List::Factory(1)->Has(1));
		$this->assertFalse(User_Character_List::Factory(1)->Has(2));
	}
	
	/**
	 * Тестируем метод для поиска персонажей, если персонаж найден.
	 */
	public function testFindIfFound()
	{
		TestHelper_Account::Add(100);
		TestHelper_Character::Add(100, 'foo', 100);
		
		$this->assertEquals(100, User_Character_List::Factory(100)->Find(100)->GetGuid());
	}
	
	/**
	 * Тестируем метод для поиска персонажей, если персонаж не найден.
	 */
	public function testFindIfNotFound()
	{
		TestHelper_Account::Add(1);

		$this->assertEquals(null, User_Character_List::Factory(1)->Find(1));
	}
};
