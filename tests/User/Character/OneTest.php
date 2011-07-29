<?php

class User_Character_OneTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		Env::Get()->db->Get('game')->Begin();
		
		User_Character_One::ResetState();
	}
	
	public function tearDown()
	{
		Env::Get()->db->Get('game')->Rollback();
		
		User_Character_One::ResetState();
	}
	
	/**
	 * Тестируем ситуацию, когда персонаж существует.
	 */
	public function testFindCharacter()
	{
		TestHelper_Character::Add(1);
		
		$character = User_Character_One::Factory(1);
		$this->assertEquals(1, $character->GetGuid());
	}
	
	/**
	 * Проверяем поиск персонажа по имени.
	 */
	public function testFindCharacterByName()
	{
		TestHelper_Character::Add(1);
		
		$character = User_Character_One::Factory('foo', User_Character_One::SEARCH_BY_NAME);
		$this->assertEquals(1, $character->GetGuid());
	}
	
	/**
	 * Тестируем ситуацию, когда персонаж не существует.
	 * @expectedException Exception_User_Character_NotFound
	 */
	public function testIfCharacterDoesntExist()
	{
		User_Character_One::Factory(1);
	}
	
	/**
	 * Проверяем поиск персонажа несколько раз:
	 * должен возвращаться один и тот же объект.
	 * @dataProvider providerIfFindCharacterTwoTimes
	 */
	public function testIfFindCharacterTwoTimes($key, $searchBy)
	{
		TestHelper_Character::Add(1);
		
		//Загружаем персонажа два раза и в каждом случае обнуляем деньги:
		$character1 = User_Character_One::Factory(1);
		$character1->SetMoney(0, 0);
		
		$character2 = User_Character_One::Factory($key, $searchBy);
		$character2->SetMoney(0, 0);
		
		//Теперь меняем деньги у первого объекта, второй должен поменяться тоже:
		$character1->SetMoney(0, 100);
		$this->assertEquals($character2->GetMoney(), $character1->GetMoney());
	}
	
	public static function providerIfFindCharacterTwoTimes()
	{
		return array(
			array(1, User_Character_One::SEARCH_BY_GUID),
			array('foo', User_Character_One::SEARCH_BY_NAME),
		);
	}
};
