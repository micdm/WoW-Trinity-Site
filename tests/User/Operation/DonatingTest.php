<?php

/**
 * @author Mic, 2010
 */
class User_Operation_DonatingTest extends User_Operation_BaseTest
{
	/**
	 * Добавляет предмет.
	 * @param integer $entry
	 */
	protected function _AddItem($entry)
	{
		Env::Get()->db->Get('game')->Query('
			INSERT INTO #world.item_template (entry)
			VALUES (:entry)
		', array(
			'entry' => array('d', $entry),
		));
	}
	
	/**
	 * Добавляет предмет в donate-систему.
	 * @param integer $entry
	 * @param float $price
	 * @param boolean $isActive
	 */
	protected function _AddItemForDonating($entry, $price, $isActive)
	{
		Env::Get()->db->Get('game')->Query('
			INSERT INTO #site.site_donate_goods (entry, price, is_active)
			VALUES (:entry, :price, :is_active)
		', array(
			'entry' => array('d', $entry),
			'price' => array('d', $price),
			'is_active' => array('d', $isActive)
		));
	}
	
	/**
	 * Запускает получение предметов.
	 * @param array $items
	 */
	protected function _GetItems($items, $price = null, $isActive = false)
	{
		TestHelper_Character::Add(1, 'foo', 1);
		Env::Get()->request->post['receiver'] = 'foo';
		Env::Get()->request->post['items'] = $items;
		
		//Добавляем на витрину:
		if ($price !== null)
		{
			$this->_AddItemForDonating($items[0], $price, $isActive);
		}
		
		User_Operation_Base::Factory('donating')->Run(false);
	}
	
	/**
	 * Запускает получение золота.
	 * @param integer $amount
	 */
	protected function _GetGold($amount)
	{
		TestHelper_Character::Add(1, 'foo', 1);
		Env::Get()->request->post = array(
			'gold' => true,
			'receiver' => 'foo',
			'amount' => $amount,
		);

		User_Operation_Base::Factory('donating')->Run(false);
	}
	
	/**
	 * Обновляет список предметов для donate-системы.
	 * @param boolean $isAdmin
	 * @param string $add
	 * @param array $prices
	 * @param array $entries
	 * @param array $active
	 * @param array $remove
	 */
	protected function _UpdateItemList($isAdmin = false, $add = '', $prices = array(), $entries = array(), $active = array(), $remove = array())
	{
		if ($isAdmin)
		{
			TestHelper_Account::Add(2, 'bar', true);
			Env::Get()->user->Authorize(2);
		}
		
		Env::Get()->request->post = array(
			'edit_items' => true,
			'add' => $add,
			'entries' => $entries,
			'active' => $active,
			'remove' => $remove,
			'prices' => $prices,
		);

		User_Operation_Base::Factory('donating')->Run(false);
	}
	
	/**
	 * Запускает перевод чеков на другой аккаунт.
	 * @param string $character
	 * @param string $account
	 * @param float $amount
	 */
	protected function _TransferCheques($character = '', $account = '', $amount = 1)
	{
		//Добавляем персонажа/аккаунт, если нужно:
		if ($character)
		{
			TestHelper_Account::Add(2, $account);
			TestHelper_Character::Add(1, $character, 2);
		}
		else if ($account)
		{
			TestHelper_Account::Add(2, $account);
		}
		
		Env::Get()->request->post = array(
			'transfer' => true,
			'character' => $character,
			'account' => $account,
			'amount' => $amount,
		);
		
		User_Operation_Base::Factory('donating')->Run(false);
	}
	
	/**
	 * Добавляет чеков тестовому пользователю.
	 * @param integer $account
	 * @param integer $delta
	 */
	protected function _AddCheques($account, $delta)
	{
		Env::Get()->user->Find($account)->GetCash()->Change($delta, 'test_reason');
	}
	
	/**
	 * Проверяем получение предметов, если ничего не выбрано.
	 * @expectedException Exception_User_Operation_BadCondition
	 */
	public function testGetItemsIfEmptyList()
	{
		$this->_GetItems(array());
	}
	
	/**
	 * Проверяем получение предметов, если выбраны несуществующие.
	 * @expectedException Exception_User_Operation_BadCondition
	 */
	public function testGetItemsIfEntriesNotFound()
	{
		$this->_GetItems(array(0));
	}
	
	/**
	 * Проверяем получение предметов, если выбраны существующие, но не выставленные на витрину.
	 * @expectedException Exception_User_Operation_BadCondition
	 */
	public function testGetItemsIfEntriesNotForSale()
	{
		TestHelper_Item::Add(1);
		$this->_GetItems(array(1));
	}
	
	/**
	 * Проверяем получение предметов, если выбраны существующие, выставленные на витрину, но неактивные
	 * @expectedException Exception_User_Operation_BadCondition
	 */
	public function testGetItemsIfEntriesNotActive()
	{
		TestHelper_Item::Add(1);
		$this->_GetItems(array(1), 0);
	}
	
	/**
	 * Проверяем получение предметов, если у пользователя не хватает чеков.
	 * @expectedException Exception_User_Operation_BadCondition
	 */
	public function testGetItemsIfNotEnoughCheques()
	{
		TestHelper_Item::Add(1);
		$this->_GetItems(array(1), 10, true);
	}
	
	/**
	 * Проверяем нормальное получение предметов.
	 * @expectedException Exception_Http_Redirected
	 */
	public function testGetItemsNormally()
	{
		TestHelper_Item::Add(1);
		$this->_AddCheques(1, 20);

		try
		{
			$this->_GetItems(array(1), 10, true);
		}
		catch (Exception_Http_Redirected $e)
		{
			//Проверяем, что баланс уменьшился:
			$this->assertEquals(10, Env::Get()->user->GetCash()->Get());

			throw $e;
		}
	}
	
	/**
	 * Проверяем получение золота с плохим количеством чеков.
	 * @dataProvider providerGetGoldIfBadAmount
	 * @expectedException Exception_User_Operation_BadCondition
	 */
	public function testGetGoldIfBadAmount()
	{
		$this->_GetGold(-3);
	}
	
	public static function providerGetGoldIfBadAmount()
	{
		return array(
			array(0),
			array(-3),
			array(0.5),
		);
	}
	
	/**
	 * Проверяем получение золота, если у пользователя не хватает чеков.
	 * @expectedException Exception_User_Operation_BadCondition
	 */
	public function testGetGoldIfNotEnoughCheques()
	{
		$this->_GetGold(10);
	}
	
	/**
	 * Проверяем нормальное получение золота.
	 * @expectedException Exception_Http_Redirected
	 */
	public function testGetGoldNormally()
	{
		$this->_AddCheques(1, 20);
		
		try
		{
			$this->_GetGold(10);
		}
		catch (Exception_Http_Redirected $e)
		{
			//Проверяем, что баланс уменьшился:
			$this->assertEquals(10, Env::Get()->user->GetCash()->Get());

			throw $e;
		}
	}
	
	/**
	 * Проверяет попытку обновить список предметов не администратором.
	 * @expectedException Exception_User_Operation_BadCondition
	 */
	public function testUpdateItemListIfUserNotAdministrator()
	{
		$this->_UpdateItemList();
	}
	
	/**
	 * Проверяем добавление предметов командой в плохом формате.
	 * @dataProvider providerAddItemsIfBadFormat
	 * @expectedException Exception_User_Operation_BadCondition
	 */
	public function testAddItemsIfBadFormat($command)
	{
		$this->_UpdateItemList(true, $command);
	}
	
	public static function providerAddItemsIfBadFormat()
	{
		return array(
			array('1'),
			array('1:'),
			array(':1'),
			array('1:1.'),
			array('1:1.1.1'),
			array('1:1.1 foo'),
			array('1:1.1 2:'),
		);
	}
	
	/**
	 * Проверяем попытку добавления вещи, которой не существует.
	 * @expectedException Exception_User_Operation_BadCondition
	 */
	public function testAddItemsIfItemNotFound()
	{
		$this->_UpdateItemList(true, '0:1');
	}
	
	/**
	 * Проверяем нормальное добавление предмета.
	 * @expectedException Exception_Http_Redirected
	 */
	public function testAddItemNormal()
	{
		$this->_AddItem(1);
		
		try
		{
			$this->_UpdateItemList(true, '1:2.2');
		}
		catch (Exception_Http_Redirected $e)
		{
			//Проверяем, что предмет добавился с нужной ценой:
			$items = World_Item::Factory(1);
			$this->assertEquals(1, count($items[0]));
			$this->assertEquals(2.2, $items[0]->GetPrice());
			
			throw $e;
		}
	}
	
	/**
	 * Проверяем попытку обновить цены плохим значением.
	 * @dataProvider providerUpdateItemsIfBadPrice
	 * @expectedException Exception_User_Operation_BadCondition
	 */
	public function testUpdateItemsIfBadPrice($price)
	{
		$this->_UpdateItemList(true, '', array($price));
	}
	
	public static function providerUpdateItemsIfBadPrice()
	{
		return array(
			array(0),
			array(-1),
		);
	}
	
	/**
	 * Проверяем нормальное обновление предметов.
	 * @expectedException Exception_Http_Redirected
	 */
	public function testUpdateItemsNormal()
	{
		//Добавляем три предмета в базу + в donate-систему:
		$this->_AddItem(1);
		$this->_AddItemForDonating(1, 1, true);
		
		$this->_AddItem(2);
		$this->_AddItemForDonating(2, 1, true);
		
		$this->_AddItem(3);
		$this->_AddItemForDonating(3, 1, true);
		
		try
		{
			//Для первого предмета обновляем цену.
			//Второй удаляем.
			//Третий делаем неактивным и обновляем ему цену.
			$this->_UpdateItemList(true, '', array(2.4, 3, 4.8), array(1, 2, 3), array(1, 2), array(2));			
		}
		catch (Exception_Http_Redirected $e)
		{
			$items = World_Item::Factory(array(1, 2, 3));
			
			$this->assertEquals(2.4, $items[0]->GetPrice());
			$this->assertTrue($items[0]->IsAvailableForDonate());
			
			$this->assertNull($items[1]->IsAvailableForDonate());
			
			$this->assertEquals(4.8, $items[2]->GetPrice());
			$this->assertFalse($items[2]->IsAvailableForDonate());
			
			throw $e;
		}
	}
	
	/**
	 * Перевод чеков без указания получателя.
	 * @expectedException Exception_User_Operation_BadCondition
	 */
	public function testTransferIfNoReceiver()
	{
		$this->_TransferCheques();
	}
	
	/**
	 * Перевод плохого количества чеков.
	 * @expectedException Exception_User_Operation_BadCondition
	 */
	public function testTransferIfBadAmount()
	{
		$this->_TransferCheques('', 'bar', -10);
	}
	
	/**
	 * Количество чеков имеет слишком много знаков после запятой.
	 * @expectedException Exception_User_Operation_BadCondition
	 */
	public function testTransferIfFractionTooLong()
	{
		$amount = '0.'.str_repeat('1', User_Operation_Action_Donating_Transfer::MAX_CHARACTERS_AFTER_POINT + 1);
		$this->_TransferCheques('', 'bar', $amount);
	}
	
	/**
	 * Недостаточно чеков для перевода.
	 * @expectedException Exception_User_Operation_BadCondition
	 */
	public function testTransferIfNotEnoughCheques()
	{
		$this->_TransferCheques('', 'bar', Env::Get()->user->GetAccount()->GetCash()->Get() + 1);
	}
	
	/**
	 * Нормальный перевод чеков.
	 * @dataProvider providerTransferNormal
	 * @expectedException Exception_Http_Redirected
	 */
	public function testTransferNormal($character, $account)
	{
		$sender = Env::Get()->user->GetAccount();
		
		//Запоминаем текущее количество чеков и добавляем новых:
		$cash = $sender->GetCash()->Get();
		$this->_AddCheques($sender->GetId(), 10);
		
		try
		{
			$this->_TransferCheques($character, $account, 10);
		}
		catch (Exception_Http_Redirected $e)
		{
			//Проверяем, что у одного уменьшилось, у другого увеличилось:
			$this->assertEquals($cash, $sender->GetCash()->Get());
			
			//Ищем получателя:
			if ($character)
			{
				//Цепочка :)
				$this->assertEquals(10, Env::Get()->user->GetAccount()->GetCharacters()->FindByName($character)->GetAccount()->GetCash()->Get());
			}
			else
			{
				$this->assertEquals(10, Env::Get()->user->FindByName($account)->GetCash()->Get());
			}
			
			throw $e;
		}
	}
	
	public static function providerTransferNormal()
	{
		return array(
			array('foo', ''),
			array('', 'bar'),
			array('foo', 'bar'),
		);
	}
};
