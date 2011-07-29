<?php

/**
 * @package User_Operation
 * @author Mic, 2010
 */
class User_Operation_TransferTest extends User_Operation_BaseTest
{
	private $_config;
	
	public function setUp()
	{
		parent::setUp();
		
		$this->_config = Env::Get()->config;
		Env::Get()->config = new TestConfig();

		//Добавляем второй аккаунт:
		TestHelper_Account::Add(2, 'bar');
		
		Env::Get()->request->post['guid'] = 1;
		Env::Get()->request->post['account'] = 'bar';
	}
	
	public function tearDown()
	{
		parent::tearDown();
		
		Env::Get()->config = $this->_config;
	}
	
	/**
	 * Проверяем перенос на премиум-аккаунт.
	 * @expectedException Exception_User_Operation_BadCondition
	 */
	public function testIfAccountPremium()
	{
		//Если премиум-аккаунты отключены, пропускаем:
		if (Env::Get()->config->Get('premiumAccountPeriod') == 0)
		{
			$this->markTestSkipped();
		}
		
		TestHelper_Character::Add(1, 'test', 1);
		Env::Get()->user->Find(2)->SetPremium();
		
		User_Operation_Base::Factory('TestOperation')->Run(false);
	}
	
	/**
	 * На указанном аккаунте слишком много персонажей.
	 * @expectedException Exception_User_Operation_BadCondition
	 */
	public function testIfTooManyCharacters()
	{
		TestHelper_Character::Add(1, 'test', 1);
		
		$max = Env::Get()->config->Get('charactersOnAccountMaxCount');
		for ($i = 0; $i < $max; $i += 1)
		{
			TestHelper_Character::Add($i + 10, 'foo'.$i, 2);
		}
		
		User_Operation_Base::Factory('TestOperation')->Run(false);
	}
	
	/**
	 * На целевом аккаунте есть персонажи противоположной фракции.
	 * @expectedException Exception_User_Operation_BadCondition
	 */
	public function testIfAnotherFaction()
	{
		//На первом аккаунте создаем человека:
		TestHelper_Character::Add(1, 'human', 1, array(
			'race' => User_Character_Race::HUMAN
		));
		
		//Создаем на втором аккаунте орка:
		TestHelper_Character::Add(2, 'orc', 2, array(
			'race' => User_Character_Race::ORC
		));
		
		Env::Get()->config->canHaveBothFactions = false;
		User_Operation_Base::Factory('TestOperation')->Run(false);
	}
	
	/**
	 * На целевом аккаунте нет персонажа достаточного уровня для рыцаря смерти.
	 * @expectedException Exception_User_Operation_BadCondition
	 */
	public function testIfNoHighLevelForDk()
	{
		//На первом аккаунте создаем рыцаря смерти:
		TestHelper_Character::Add(1, 'first', 1, array(
			'class' => User_Character_Class::DEATHKNIGHT
		));
		
		//На втором аккаунте создаем персонажа уровнем ниже, чем требуется для рыцаря смерти:
		TestHelper_Character::Add(2, 'second', 2, array(
			'level' => Env::Get()->config->Get('minLevelForDeathknight') - 1
		));

		User_Operation_Base::Factory('TestOperation')->Run(false);
	}
	
	/**
	 * На исходном аккаунте только один персонаж высокого уровня.
	 * @expectedException Exception_User_Operation_BadCondition
	 */
	public function testIfOnlyOneHighLevel()
	{
		//Создаем персонажа высокого уровня на первом аккаунте:
		TestHelper_Character::Add(1, 'first', 1, array(
			'level' => Env::Get()->config->Get('minLevelForDeathknight') + 1
		));
		
		//На первом же аккаунте создаем рыцаря смерти:
		TestHelper_Character::Add(2, 'second', 1, array(
			'class' => User_Character_Class::DEATHKNIGHT
		));
		
		User_Operation_Base::Factory('TestOperation')->Run(false);
	}
	
	/**
	 * Обычная ситуация.
	 * @expectedException Exception_Http_Redirected
	 */
	public function testNormal()
	{
		//Создаем персонажа на первом аккаунте:
		TestHelper_Character::Add(1, 'first', 1);
		
		try
		{
			User_Operation_Base::Factory('TestOperation')->Run(false);
		}
		catch (Exception_Http_Redirected $e)
		{
			//Проверяем, что аккаунт сменился:
			$character = Env::Get()->user->GetAccount()->GetCharacters()->Find(1);
			$this->assertEquals(2, $character->GetAccount()->GetId());
			
			//Проверяем, что персонаж пометился как перенесенный:
			$count = Env::Get()->db->Get('game')->Query('
				SELECT COUNT(*)
				FROM #site.site_operation_transfer_complete
				WHERE guid = 1
			')->FetchOne();
			$this->assertEquals(1, $count);
			
			throw $e;
		}
	}
	
	/**
	 * На исходном аккаунте много персонажей высокого уровня.
	 * @expectedException Exception_Http_Redirected
	 */
	public function testIfManyHighLevels()
	{
		//Создаем персонажа высокого уровня на первом аккаунте:
		TestHelper_Character::Add(1, 'first', 1, array(
			'level' => Env::Get()->config->Get('minLevelForDeathknight') + 1
		));
		
		//Создаем еще одного персонажа высокого уровня на первом аккаунте:
		TestHelper_Character::Add(2, 'second', 1, array(
			'level' => Env::Get()->config->Get('minLevelForDeathknight') + 1
		));
		
		//На первом же аккаунте создаем рыцаря смерти:
		TestHelper_Character::Add(3, 'third', 1, array(
			'class' => User_Character_Class::DEATHKNIGHT
		));
		
		User_Operation_Base::Factory('TestOperation')->Run(false);
	}
};


class TestConfig extends Config
{
	public function Get($name, $config = 'base')
	{
		return (isset($this->$name)) ? $this->$name : parent::Get($name, $config);
	}
};


class User_Operation_TestOperation extends User_Operation_Transfer
{
	protected function _Setup()
	{
		$this->_AddAction('main', 'TestActionNormal');
	}
};

class User_Operation_Action_TestActionNormal extends User_Operation_Action_Transfer_Main
{
	protected function _Setup()
	{
		parent::_Setup();

		$this->_isMailConfirmRequired = false;
	}
};
