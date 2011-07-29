<?php

/**
 * @author Mic, 2010
 */
class User_Operation_MainTest extends User_Operation_BaseTest
{
	public function setUp()
	{
		parent::setUp();
		
		User_Operation_TestOperation0::$params = array();
		User_Operation_TestOperation1::$params = array();
		User_Operation_TestOperation2::$params = array();
		
		unset(Config_Base::$operations['testoperation0']['main']);
	}
	
	/**
	 * Добавляет в таблицу подтверждений новую запись.
	 * @param integer $user
	 * @param integer $status
	 * @param string $operation
	 * @param string $action
	 */
	protected function _addMailConfirmRecord($account = 1, $ip = '127.0.0.1', $status = 0, $operation = 'TestOperation4', $action = 'main', $time = 'now')
	{
		Env::Get()->db->Get('game')->Query('
			INSERT INTO #site.site_operation_mail_confirm (operation, action, account, created, ip, code, data)
			VALUES (:operation, :action, :account, :created, :ip, \'\', \'\')
		', array(
			'operation' => array('s', $operation),
			'action' => array('s', $action),
			'account' => array('d', $account),
			'created' => array('s', date('Y-m-d H:i:s', strtotime($time))),
			'ip' => array('s', $ip),
		));
	}
	
	/**
	 * Тестируем нормальное поведение.
	 * @expectedException Exception_Http_Redirected
	 */
	public function testNormal()
	{
		TestHelper_Character::Add(1, 'foo', 1);
		Env::Get()->request->post['guid'] = 1;

		//Запускаем:
		$operation = User_Operation_Base::Factory('TestOperation2');
		$operation->Run(false);
	}
	
	/**
	 * Поле не указано в POST.
	 * @expectedException Exception_User_Operation_PlainField_Need
	 */
	public function testIfFieldNotSelected()
	{
		User_Operation_Base::Factory('TestOperation0')->Run(false);
	}
	
	/**
	 * Поле не указано в POST, но это нормально.
	 * @expectedException Exception_Http_Redirected
	 */
	public function testIfFieldNotSelectedButCanBeNull()
	{
		User_Operation_TestOperation0::$params = array('canBeNull' => true);
		User_Operation_Base::Factory('TestOperation0')->Run(false);
	}
	
	/**
	 * Аккаунт не указан в POST.
	 * @expectedException Exception_User_Operation_Account_Need
	 */
	public function testIfAccountNotSelected()
	{
		User_Operation_Base::Factory('TestOperation1')->Run(false);
	}
	
	/**
	 * Аккаунт не указан, но это и необязательно.
	 * @expectedException Exception_Http_Redirected
	 */
	public function testIfAccountEmptyButCanBe()
	{
		Env::Get()->request->post['account'] = '';
		
		User_Operation_TestOperation1::$params = array('canBeEmpty' => true);
		User_Operation_Base::Factory('TestOperation1')->Run(false);
	}
	
	/**
	 * Аккаунт не найден по идентификатору.
	 * @expectedException Exception_User_Operation_Account_NotFound
	 */
	public function testIfAccountNotFound()
	{
		Env::Get()->request->post['account'] = 2;
		User_Operation_Base::Factory('TestOperation1')->Run(false);
	}
	
	/**
	 * Аккаунт не найден по имени.
	 * @expectedException Exception_User_Operation_Account_NotFound
	 */
	public function testIfAccountNotFoundByName()
	{
		Env::Get()->request->post['account'] = 'test';
		
		User_Operation_TestOperation1::$params = array('isName' => true);
		User_Operation_Base::Factory('TestOperation1')->Run(false);
	}
	
	/**
	 * Аккаунт совпадает с текущим.
	 * @expectedException Exception_User_Operation_Account_Current
	 */
	public function testIfAccountCurrent()
	{
		Env::Get()->request->post['account'] = 1;
		User_Operation_TestOperation1::$params = array('mustDiffer' => true);
		
		User_Operation_Base::Factory('TestOperation1')->Run(false);
	}
	
	/**
	 * Аккаунт забанен.
	 * @expectedException Exception_User_Operation_Account_Banned
	 */
	public function testIfAccountBanned()
	{
		TestHelper_Account::Ban(2);
		TestHelper_Account::Add(2, 'bar');
		Env::Get()->request->post['account'] = 2;

		User_Operation_Base::Factory('TestOperation1')->Run(false);
	}
	
	/**
	 * Аккаунт забанен, но может таковым быть.
	 * @expectedException Exception_Http_Redirected
	 */
	public function testIfAccountBannedButCanBe()
	{
		TestHelper_Account::Add(2, 'bar');
		Env::Get()->request->post['account'] = 2;
		
		TestHelper_Account::Ban(2);

		User_Operation_TestOperation1::$params = array('canBeBanned' => true);
		User_Operation_Base::Factory('TestOperation1')->Run(false);
	}
	
	/**
	 * Персонаж не указан в POST.
	 * @expectedException Exception_User_Operation_Character_Need
	 */
	public function testIfCharacterNotSelected()
	{
		User_Operation_Base::Factory('TestOperation2')->Run(false);
	}
	
	/**
	 * Персонаж не указан, но это и необязательно.
	 * @expectedException Exception_Http_Redirected
	 */
	public function testIfCharacterEmptyButCanBe()
	{
		Env::Get()->request->post['guid'] = 0;
		
		User_Operation_TestOperation2::$params = array('canBeEmpty' => true);
		User_Operation_Base::Factory('TestOperation2')->Run(false);
	}
	
	/**
	 * Персонаж указан несуществующий.
	 * @expectedException Exception_User_Operation_Character_NotFound
	 */
	public function testIfCharacterNotFound()
	{
		Env::Get()->request->post['guid'] = 1;
		User_Operation_Base::Factory('TestOperation2')->Run(false);
	}
	
	/**
	 * Персонаж не найден по имени.
	 * @expectedException Exception_User_Operation_Character_NotFound
	 */
	public function testIfCharacterNotFoundByName()
	{
		Env::Get()->request->post['guid'] = 'test';
		
		User_Operation_TestOperation1::$params = array('isName' => true);
		User_Operation_Base::Factory('TestOperation2')->Run(false);
	}
	
	/**
	 * Персонаж находится в игровом мире.
	 * @expectedException Exception_User_Operation_Character_Online
	 */
	public function testIfCharacterOnline()
	{
		TestHelper_Character::Add(1, 'foo', 1, array(
			'online' => 1
		));
		
		Env::Get()->request->post['guid'] = 1;
		User_Operation_Base::Factory('TestOperation2')->Run(false);
	}
	
	/**
	 * Персонаж находится в игровом мире, но он это может.
	 * @expectedException Exception_Http_Redirected
	 */
	public function testIfCharacterOnlineButCanBe()
	{
		TestHelper_Character::Add(1, 'foo', 1, array('online' => 1));
		Env::Get()->request->post['guid'] = 1;
		
		User_Operation_TestOperation2::$params = array('canBeOnline' => true);
		User_Operation_Base::Factory('TestOperation2')->Run(false);
	}
	
	/**
	 * Персонаж находится на забаненном аккаунте.
	 * @expectedException Exception_User_Operation_Character_Banned
	 */
	public function testIfCharacterBanned()
	{
		TestHelper_Account::Ban(2);
		TestHelper_Account::Add(2, 'bar');
		TestHelper_Character::Add(1, 'foo', 2);

		Env::Get()->request->post['guid'] = 1;
		User_Operation_Base::Factory('TestOperation2')->Run(false);
	}
	
	/**
	 * Персонаж находится на забаненном аккаунте, но может это делать.
	 * @expectedException Exception_Http_Redirected
	 */
	public function testIfCharacterBannedButCanBe()
	{
		TestHelper_Character::Add(1, 'foo', 1);
		TestHelper_Account::Ban(1);

		Env::Get()->request->post['guid'] = 1;
		
		User_Operation_TestOperation2::$params = array('canBeBanned' => true);
		User_Operation_Base::Factory('TestOperation2')->Run(false);
	}
	
	/**
	 * Персонаж не принадлежит аккаунту.
	 * @expectedException Exception_User_Operation_Character_NotBelong
	 */
	public function testIfCharacterNotBelong()
	{
		TestHelper_Account::Add(2, 'bar');
		TestHelper_Character::Add(1, 'foo', 2);
		
		Env::Get()->request->post['guid'] = 1;
		
		User_Operation_TestOperation2::$params = array('mustBelong' => true);
		User_Operation_Base::Factory('TestOperation2')->Run(false);
	}
	
	/**
	 * Персонаж принадлежит аккаунту.
	 * @expectedException Exception_User_Operation_Character_Belong
	 */
	public function testIfCharacterBelong()
	{
		TestHelper_Character::Add(1, 'foo', 1);
		
		Env::Get()->request->post['guid'] = 1;
		
		User_Operation_TestOperation2::$params = array('mustNotBelong' => true);
		User_Operation_Base::Factory('TestOperation2')->Run(false);
	}
	
	/**
	 * Проверяем запись в таблицы истории.
	 */
	public function testHistory()
	{
		TestHelper_Account::Add(2, 'bar');
		TestHelper_Character::Add(2, 'foo', 2);
		
		Env::Get()->request->post['plain'] = 2;
		Env::Get()->request->post['character'] = 2;
		Env::Get()->request->post['account'] = 2;
		
		try
		{
			User_Operation_Base::Factory('TestOperation3')->Run(false);
		}
		catch (Exception_Http_Redirected $e)
		{
			$db = Env::Get()->db->Get('game');
			
			//Получаем последнюю запись из таблицы истории:
			$lastId = $db->Query('
				SELECT MAX(id)
				FROM #site.site_operation_history
			')->FetchOne();
			
			//Проверяем, что записалось в поля дополнительных таблиц:
			$result = $db->Query('
				SELECT
					ohp.value,
					oha.account_id,
					ohch.guid,
					ohcu.value
				FROM #site.site_operation_history AS oh
					INNER JOIN #site.site_operation_history_plain AS ohp ON(ohp.history_id = oh.id)
					INNER JOIN #site.site_operation_history_accounts AS oha ON(oha.history_id = oh.id)
					INNER JOIN #site.site_operation_history_characters AS ohch ON(ohch.history_id = oh.id)
					INNER JOIN #site.site_operation_history_custom AS ohcu ON(ohcu.history_id = oh.id)
				WHERE oh.id = :id
			', array(
				'id' => array('d', $lastId)
			))->FetchRow();
			
			//Все должны быть по единице:
			foreach ($result as $value)
			{
				$this->assertEquals(2, $value);
			}
		}
	}
	
	/**
	 * Для подтверждения требуется хороший адрес почты, но он не был указан.
	 * @expectedException Exception_User_Operation_BadEmail
	 * @dataProvider providerIfBadEmailButConfirmRequired
	 */
	public function testIfBadEmailButConfirmRequired($email)
	{
		Env::Get()->user->GetAccount()->SetEmail($email);
		User_Operation_Base::Factory('TestOperation4')->Run(false);
	}
	
	public static function providerIfBadEmailButConfirmRequired()
	{
		return array(
			array(''),
			array('foo'),
		);
	}
	
	/**
	 * Проверяем нормальное создание подтверждения.
	 * @dataProvider providerNormalConfirm
	 * @expectedException Exception_Http_Redirected
	 */
	public function testNormalConfirm($operation, $action, $account, $ip, $time)
	{
		//Добавляем подтверждение, но для другой операции/пользователя:
		$this->_addMailConfirmRecord($account, $ip, 0, $operation, $action, $time);
		if ($account == 0)
		{
			Env::Get()->user->Deauthorize();
		}
		
		try
		{
			User_Operation_Base::Factory('TestOperation4')->Run(false);
		}
		catch (Exception_Http_Redirected $e)
		{
			//Проверяем, что запись про подтверждение создалась:
			$data = Env::Get()->db->Get('game')->Query('
				SELECT *
				FROM #site.site_operation_mail_confirm
				ORDER BY id DESC
				LIMIT 1
			')->FetchRow();
			
			$this->assertEquals('testoperation4', $data['operation']);
			$this->assertEquals('main', $data['action']);
			$this->assertEquals(Env::Get()->user->GetAccount()->GetId(), $data['account']);
			
			throw $e;
		}
	}
	
	public static function providerNormalConfirm()
	{
		return array(
			array('foo', 'main', 1, '', 'now'),
			array('TestOperation4', 'foo', 1, '', 'now'),
			array('TestOperation4', 'main', 2, '', 'now'),
			array('TestOperation4', 'main', 0, 'foo', 'now'),
			array('TestOperation4', 'main', 1, '', '-'.(User_Operation_Action_Base::TIME_BETWEEN_CONFIRMS + 1).' seconds'),
		);
	}
	
	/**
	 * Проверяем создание подтверждения, если подобное было создано недавно.
	 * @dataProvider providerConfirmIfConfirmExists
	 * @expectedException Exception_User_Operation_BadCondition
	 */
	public function testConfirmIfConfirmExists($user, $ip)
	{
		//Добавляем подтверждение для данного пользователя:
		$this->_addMailConfirmRecord($user, $ip, 0);
		if ($user == 0)
		{
			Env::Get()->user->Deauthorize();
		}
		
		User_Operation_Base::Factory('TestOperation4')->Run(false);
	}
	
	public static function providerConfirmIfConfirmExists()
	{
		return array(
			array(1, ''),
			array(0, Env::Get()->request->GetIp()),
		);
	}
	
	/**
	 * Отправка подтверждения специальному получателю.
	 * Однажды сломалось восстановление паролей из-за неправильного порядка проверок.
	 * @expectedException Exception_Http_Redirected
	 */
	public static function testSendConfirmToCustomReceiver()
	{
		Env::Get()->request->post['account'] = 1;
		User_Operation_Base::Factory('TestOperation5')->Run(false);
	}
	
	/**
	 * Недостаточно чеков для проведения операции.
	 * @expectedException Exception_User_Operation_NotEnoughCheques
	 */
	public function testIfNotEnoughCheques()
	{
		Config_Base::$operations['testoperation0']['main'] = 999999;
		Env::Get()->request->post['field'] = 1;

		User_Operation_Base::Factory('TestOperation0')->Run(false);
	}
	
	/**
	 * Проверяем взимание платежа.
	 * @expectedException Exception_Http_Redirected
	 */
	public function testTakeOffPayment()
	{
		$cash = Env::Get()->user->GetCash();
		$cash->Change(10, 'test_reason');
		$start = $cash->Get();
		
		Config_Base::$operations['testoperation0']['main'] = 1;
		Env::Get()->request->post['field'] = 1;
		
		try
		{
			User_Operation_Base::Factory('TestOperation0')->Run(false);
		}
		catch (Exception_Http_Redirected $e)
		{
			$this->assertEquals(1, $start - $cash->Get());
			throw $e;
		}
	}
};


class User_Operation_TestOperation0 extends User_Operation_Base
{
	public static $params = array();
	
	protected function _Setup()
	{
		$this->_AddAction('main', 'TestAction0');
	}
};

class User_Operation_Action_TestAction0 extends User_Operation_Action_Base
{
	protected function _Setup()
	{
		$this->_AddPlainField('field', User_Operation_TestOperation0::$params);
	}
	
	protected function _DoSomeActions()
	{
		
	}
};


class User_Operation_TestOperation1 extends User_Operation_TestOperation0
{
	protected function _Setup()
	{
		$this->_AddAction('main', 'TestAction1');
	}
};

class User_Operation_Action_TestAction1 extends User_Operation_Action_TestAction0
{
	protected function _Setup()
	{
		$this->_AddAccount('account', User_Operation_TestOperation1::$params);
	}
};


class User_Operation_TestOperation2 extends User_Operation_TestOperation0
{
	protected function _Setup()
	{
		$this->_AddAction('main', 'TestAction2');
	}
};

class User_Operation_Action_TestAction2 extends User_Operation_Action_TestAction0
{
	protected function _Setup()
	{
		$this->_AddCharacter('guid', User_Operation_TestOperation2::$params);
	}
};


//Для тестирования истории:
class User_Operation_TestOperation3 extends User_Operation_TestOperation0
{
	protected function _Setup()
	{
		$this->_AddAction('main', 'TestAction3');
	}
};

class User_Operation_Action_TestAction3 extends User_Operation_Action_TestAction0
{
	protected function _Setup()
	{
		$this
			->_AddPlainField('plain')
			->_AddAccount('account')
			->_AddCharacter('character')
			->_AddLogGenerator('custom', array($this, '_Generator'));
	}
	
	protected function _Generator()
	{
		return 2;
	}
};


//Для тестирования почтовых подтверждений:
class User_Operation_TestOperation4 extends User_Operation_TestOperation0
{
	protected function _Setup()
	{
		$this->_AddAction('main', 'TestAction4');
	}
};

class User_Operation_Action_TestAction4 extends User_Operation_Action_TestAction0
{
	protected function _Setup()
	{
		$this->_SetMailConfirmRequired();
	}
};


//Для тестирования почтовых подтверждений, когда получатель берется из специального места:
class User_Operation_TestOperation5 extends User_Operation_TestOperation4
{
	protected function _Setup()
	{
		$this->_AddAction('main', 'TestAction5');
	}
};

class User_Operation_Action_TestAction5 extends User_Operation_Action_TestAction4
{
	protected function _Setup()
	{
		parent::_Setup();
		$this->_AddAccount('account');
	}
	
	protected function _GetMailConfirmReciever()
	{
		return $this->GetAccount();
	}
};
