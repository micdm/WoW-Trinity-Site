<?php

/**
 * @package User_Operation
 * @author Mic, 2010
 */
class User_Operation_ExchangeTest extends User_Operation_BaseTest
{
	/**
	 * Подготавливает аккаунты/персонажей для создания заявки.
	 */
	protected function _prepareForRequest()
	{
		TestHelper_Character::Add(1, 'foo', 1);
		
		TestHelper_Account::Add(2, 'bar');
		TestHelper_Character::Add(2, 'bar', 2);
		
		Env::Get()->request->post['my'] = 1;
		Env::Get()->request->post['its'] = 'bar';
	}
	
	/**
	 * Добавляет заявку.
	 */
	protected function _addRequest($my = 1, $its = 2)
	{
		Env::Get()->db->Get('game')->Query('
			INSERT INTO #site.site_operation_exchange (guid_my, guid_its)
			VALUES (:guidMy, :guidIts)
		', array(
			'guidMy' => array('d', $my),
			'guidIts' => array('d', $its),
		));
	}
	
	/**
	 * Проверяем обмен с премиум-аккаунтом.
	 * @dataProvider providerIfPremiumAccount
	 * @expectedException Exception_User_Operation_BadCondition
	 */
	public function testIfPremiumAccount($account)
	{
		$this->_prepareForRequest();
		Env::Get()->user->Find($account)->SetPremium();
		
		User_Operation_Base::Factory('TestOperation')->Run(false);
	}
	
	public static function providerIfPremiumAccount()
	{
		return array(
			array(1),
			array(2),
		);
	}
	
	/**
	 * Проверяем создание новой заявки.
	 * @expectedException Exception_Http_Redirected
	 */
	public function testNormal()
	{
		$this->_prepareForRequest();
		
		try
		{
			User_Operation_Base::Factory('TestOperation')->Run(false);
		}
		catch (Exception_Http_Redirected $e)
		{
			//Проверяем, что заявка записала в таблицу:
			$row = Env::Get()->db->Get('game')->Query('
				SELECT
					guid_my AS guidMy,
					guid_its AS guidIts
				FROM #site.site_operation_exchange
				LIMIT 1
			')->FetchRow();
			
			$this->assertEquals(1, $row['guidMy']);
			$this->assertEquals(2, $row['guidIts']);
			
			throw $e;
		}
	}
	
	/**
	 * Проверяем удаление заявки, когда вместо списка идентификаторов пришло что-то левое.
	 * @dataProvider providerForRemoveIfBadIdsGiven
	 * @expectedException Exception_Runtime
	 */
	public function testRemoveIfBadIdsGiven($ids)
	{
		Env::Get()->request->post = array(
			'remove' => true,
			'ids' => $ids
		);
		
		User_Operation_Base::Factory('TestOperation')->Run(false);
	}
	
	public static function providerForRemoveIfBadIdsGiven()
	{
		return array(
			//Пришел не массив:
			array(true),
			
			//Пришел пустой массив:
			array(array())
		);
	}
	
	/**
	 * Проверяем удаление, когда заявка принадлежит чужому аккаунту.
	 * @expectedException Exception_User_Operation_BadCondition
	 */
	public function testRemoveIfRequestIsNotBelong()
	{
		$this->_addRequest();
		
		Env::Get()->request->post = array(
			'remove' => true,
			'ids' => array(Env::Get()->db->Get('game')->GetLastId())
		);
		
		User_Operation_Base::Factory('TestOperation')->Run(false);
	}
	
	/**
	 * Проверяем нормальное удаление.
	 * @expectedException Exception_Http_Redirected
	 */
	public function testRemove()
	{
		TestHelper_Character::Add(1, 'foo', 1);

		$this->_addRequest();
		
		$id = Env::Get()->db->Get('game')->GetLastId();
		Env::Get()->request->post = array(
			'remove' => true,
			'ids' => array($id)
		);
		
		try
		{
			User_Operation_Base::Factory('TestOperation')->Run(false);
		}
		catch (Exception_Http_Redirected $e)
		{
			//Проверяем, что заявка записала в таблицу:
			$count = Env::Get()->db->Get('game')->Query('
				SELECT COUNT(*)
				FROM #site.site_operation_exchange
				WHERE id = :id
			', array(
				'id' => array('d', $id)
			))->FetchOne();
			
			$this->assertEquals(0, $count);
			
			throw $e;
		}
	}
	
	/**
	 * Проверяем подтверждение заявки, когда заявка не найдена.
	 * @expectedException Exception_User_Operation_BadCondition
	 */
	public function testAcceptIfRequestNotFound()
	{
		TestHelper_Character::Add(1, 'foo', 1);

		TestHelper_Account::Add(2, 'bar');
		TestHelper_Character::Add(2, 'bar', 2);
		
		Env::Get()->request->post = array(
			'accept' => true,
			'my' => 2,
			'its' => 1
		);
		
		User_Operation_Base::Factory('TestOperation')->Run(false);
	}
	
	/**
	 * Проверяем нормальное подтверждение заявки.
	 * @expectedException Exception_Http_Redirected
	 */
	public function testAccept()
	{
		TestHelper_Character::Add(2, 'foo', 1);

		TestHelper_Account::Add(2, 'bar');
		TestHelper_Character::Add(1, 'bar', 2);

		$this->_addRequest();
		$this->_addRequest(1, 3);
		$this->_addRequest(2, 3);
		
		Env::Get()->request->post = array(
			'accept' => true,
			'my' => 1,
			'its' => 2
		);
		
		try
		{
			User_Operation_Base::Factory('TestOperation')->Run(false);
		}
		catch (Exception_Http_Redirected $e)
		{
			//Проверяем, что аккаунты поменялись:
			$my = Env::Get()->user->GetAccount()->GetCharacters()->Find(1);
			$its = Env::Get()->user->GetAccount()->GetCharacters()->Find(2);
			
			$this->assertEquals(1, $my->GetAccount()->GetId());
			$this->assertEquals(2, $its->GetAccount()->GetId());
			
			//Проверяем, что заявок не осталось:
			$count = Env::Get()->db->Get('game')->Query('
				SELECT COUNT(*)
				FROM #site.site_operation_exchange
				WHERE FALSE
					OR guid_my IN(1, 2)
					OR guid_its IN(1, 3)
			')->FetchOne();
			
			$this->assertEquals(0, $count);
			
			throw $e;
		}
	}
};


class User_Operation_TestOperation extends User_Operation_Exchange
{
	protected function _Setup()
	{
		parent::_Setup();
		
		$this->_AddAction('main', 'TestActionNormal');
		$this->_AddAction('accept', 'TestActionAccept');
	}
};

class User_Operation_Action_TestActionNormal extends User_Operation_Action_Exchange_Main
{
	protected function _Setup()
	{
		parent::_Setup();

		$this->_isMailConfirmRequired = false;
	}
};

class User_Operation_Action_TestActionAccept extends User_Operation_Action_Exchange_Accept
{
	protected function _Setup()
	{
		parent::_Setup();

		$this->_isMailConfirmRequired = false;
	}
};
