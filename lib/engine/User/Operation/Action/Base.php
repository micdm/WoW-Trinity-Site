<?php

/**
 * Базовый класс для всех действий внутри операции.
 * @package User_Operation_Action
 * @author Mic, 2010
 */
abstract class User_Operation_Action_Base
{
	const FIELD_TYPE_PLAIN							= 0;
	const FIELD_TYPE_ACCOUNT						= 1;
	const FIELD_TYPE_CHARACTER						= 2;
	
	/**
	 * Минимальное время между двумя почтовыми запросами.
	 * @var integer
	 */
	const TIME_BETWEEN_CONFIRMS						= 1800;
	
	/**
	 * Объект операции, в рамках которой выполняется действие.
	 * @var User_Operation_Base
	 */
	protected $_operation;
	
	/**
	 * Название действия.
	 * @var string
	 */
	protected $_name;
	
	/**
	 * Список ключей POST, в которых хранится информация о целях.
	 * @var array
	 */
	protected $_fields = array();
	
	/**
	 * Информация для операции.
	 * @var array
	 */
	protected $_plain = array();
	
	/**
	 * Список участвующих аккаунтов.
	 * @var array
	 */
	protected $_accounts = array();
	
	/**
	 * Список участвующих персонажей.
	 * @var array
	 */
	protected $_characters = array();
	
	/**
	 * Список методов, результат работы которых нужно записать в лог.
	 * @var array
	 */
	protected $_logGenerators = array();
	
	/**
	 * Требуется ли подтверждение действия по почте?
	 * @var bool
	 */
	protected $_isMailConfirmRequired = false;
	
	/**
	 * Нужно ли перенаправить пользователя по окончании операции?
	 * @var boolean
	 */
	protected $_isRedirectRequired = true;
	
	/**
	 * Подтверждено ли действие по почте.
	 * @var bool
	 */
	protected $_isConfirmed = false;
	
	/**
	 * Сообщение(я) об успешно выполненной операции
	 * @var mixed
	 */
	protected $_successMessages = '';

	/**
	 * @param User_Operation_Base $operation
	 * @param string $name
	 */
	public function __construct(User_Operation_Base $operation, $name)
	{
		$this->_operation = $operation;
		$this->_name = $name;
		
		$this->_Setup();
	}

	/**
	 * Настройка операции.
	 */
	abstract protected function _Setup();

	/**
	 * Основной функционал действия.
	 */
	abstract protected function _DoSomeActions();
	
	/**
	 * Возвращает описание действия для истории.
	 * @param array $accounts
	 * @param array $characters
	 * @param array $plain
	 * @param array $custom
	 * @return string
	 */
	public function GetDescription($accounts, $characters, $plain, $custom)
	{
		return 'Здесь должно было быть описание';
	}
	
	/**
	 * Устанавливает флаг необходимости подтверждения.
	 * @return User_Operation_Action_Base
	 */
	protected function _SetMailConfirmRequired()
	{
		$this->_isMailConfirmRequired = true;
		return $this;
	}
	
	/**
	 * Требуется ли подтверждение по почте?
	 * @return bool
	 */
	public function IsMailConfirmRequired()
	{
		return $this->_isMailConfirmRequired;
	}

	/**
	 * Устанавливает флаг необходимости перенаправления.
	 * @return User_Operation_Action_Base
	 */
	protected function _SetRedirectNotRequired()
	{
		$this->_isRedirectRequired = false;
		return $this;
	}
	
	/**
	 * Нужно ли обновить страницу?
	 * @return boolean
	 */
	public function IsRedirectRequired()
	{
		return $this->_isRedirectRequired;
	}

	/**
	 * Возвращает заполненные пользователем требуемые поля.
	 * @return array
	 */
	protected function _GetInput()
	{
		$result = array();
		foreach ($this->_fields as $name => $params)
		{
			$result[$name] = Env::Get()->request->Post($name);
		}
		
		return $result;
	}
	
	/**
	 * Возвращает тему письма с подтверждением.
	 * @return string
	 */
	protected function _GetSubjectForMailConfirm()
	{
		return '';
	}

	/**
	 * Возвращает обработанное простое поле.
	 * @param string $key
	 * @return mixed
	 */
	public function GetPlainField($key = null)
	{
		return $key ? $this->_plain[$key] : reset($this->_plain);
	}
	
	/**
	 * Возвращает загруженный аккаунт.
	 * @param string $key
	 * @return User_Account
	 */
	public function GetAccount($key = null)
	{
		return $key ? $this->_accounts[$key] : reset($this->_accounts);
	}
	
	/**
	 * Возвращает загруженного персонажа.
	 * @param string $key
	 * @return User_Character
	 */
	public function GetCharacter($key = null)
	{
		return $key ? $this->_characters[$key] : reset($this->_characters);
	}
	
	/**
	 * Возвращает сообщение(я) об успешном исходе.
	 * @return mixed
	 */
	public function GetSuccessMessages()
	{
		return $this->_successMessages;
	}
	
	/**
	 * Устанавливает сообщение(я) об успешной операции.
	 * @param mixed $msgs
	 * @return User_Operation_Action_Base
	 */
	protected function _SetSuccessMessages($msgs)
	{
		$this->_successMessages = $msgs;
		return $this;
	}

	/**
	 * Добавляет простое поле.
	 * Параметры:
	 *  noHistory - не сохранять в истории
	 *  datatype - тип данных, будет сделан settype,
	 *  canBeNull - может быть не указан во входных данных
	 *  
	 * @param string $field
	 * @param array $params
	 * @return User_Operation_Action_Base
	 */
	protected function _AddPlainField($field, $params = array())
	{
		$params['type'] = self::FIELD_TYPE_PLAIN;
		$this->_fields[$field] = $params;

		return $this;
	}
	
	/**
	 * Добавляет аккаунт-участник.
	 * Параметры:
	 *  isName - является именем, а не идентификатором
	 *  mustDiffer - должен НЕ совпадать с текущим аккаунтом
	 *  canBeBanned - может быть забанен
	 *  canBeEmpty - может быть не указан
	 *  
	 * @param string $field
	 * @param array $params
	 * @return User_Operation_Action_Base
	 */
	protected function _AddAccount($field, $params = array())
	{
		$params['type'] = self::FIELD_TYPE_ACCOUNT;
		$this->_fields[$field] = $params;

		return $this;
	}
	
	/**
	 * Добавляет персонажа-участника.
	 * Параметры:
	 *  isName - является именем, а не идентификатором
	 *  mustBelong - должен принадлежать текущему аккаунты
	 *  mustNotBelong - должен НЕ принадлежать текущему аккаунту
	 *  canBeOnline - может быть онлайн
	 *  canBeBanned - может быть забанен
	 *  canBeEmpty - может быть не указан
	 * 
	 * @param string $field
	 * @param array $params
	 * @return User_Operation_Action_Base
	 */
	protected function _AddCharacter($field, $params = array())
	{
		$params['type'] = self::FIELD_TYPE_CHARACTER;
		$this->_fields[$field] = $params;

		return $this;
	}
	
	/**
	 * Добавляет специфический генератор данных для лога.
	 * @param string $key
	 * @param callback $method
	 * @param array $params
	 * @return User_Operation_Action_Base
	 */
	protected function _AddLogGenerator($key, $method, $params = array())
	{
		$this->_logGenerators[$key] = array(
			'method' => $method,
			'params' => $params
		);
		
		return $this;
	}
	
	/**
	 * Проверяет простые поля.
	 * Сохраняет себе их значения.
	 */
	protected function _CheckPlainFields()
	{
		foreach ($this->_fields as $name => $data)
		{
			if ($data['type'] != self::FIELD_TYPE_PLAIN)
			{
				continue;
			}
			
			//Ищем:
			$value = $this->_operation->GetFieldValue($name);
			if ($value === null && empty($data['canBeNull']))
			{
				throw new Exception_User_Operation_PlainField_Need();
			}
			
			//Кастуем к нужному типу:
			if (isset($data['datatype']))
			{
				settype($value, $data['datatype']);
			}
			
			$this->_plain[$name] = $value;
		}
	}
	
	/**
	 * Проверяет, все ли аккаунты указаны корректно.
	 * По пути сохраняет объекты аккаунтов.
	 */
	protected function _CheckAccounts()
	{
		foreach ($this->_fields as $name => $data)
		{
			if ($data['type'] != self::FIELD_TYPE_ACCOUNT)
			{
				continue;
			}
			
			//В запросе должен быть указан аккаунт:
			$account = $this->_operation->GetFieldValue($name);
			if (empty($account))
			{
				//Если аккаунт должен быть указан:
				if (empty($data['canBeEmpty']))
				{
					throw new Exception_User_Operation_Account_Need();
				}
				
				$account = null;
			}
			else
			{
				//Ищем аккаунт:
				$account = isset($data['isName']) ? Env::Get()->user->FindByName($account) : Env::Get()->user->Find($account);
				if (empty($account))
				{
					throw new Exception_User_Operation_Account_NotFound();
				}
				
				//Аккаунт не должен совпадать с текущим:
				if (isset($data['mustDiffer']) && $account->GetId() == Env::Get()->user->GetAccount()->GetId())
				{
					throw new Exception_User_Operation_Account_Current();
				}
				
				//Аккаунт не должен быть забанен:
				if ($account->IsBanned() && empty($data['canBeBanned']))
				{
					throw new Exception_User_Operation_Account_Banned();
				}
			}
				
			$this->_accounts[$name] = $account;
		}
	}
	
	/**
	 * Проверяет, все ли персонажи указаны корректно.
	 * По пути сохраняет объекты персонажей.
	 */
	protected function _CheckCharacters()
	{
		foreach ($this->_fields as $name => $data)
		{
			if ($data['type'] != self::FIELD_TYPE_CHARACTER)
			{
				continue;
			}
			
			//Проверяем, что в запросе указан персонаж:
			$character = $this->_operation->GetFieldValue($name);
			if (empty($character))
			{
				if (empty($data['canBeEmpty']))
				{
					throw new Exception_User_Operation_Character_Need();
				}
				
				$character = null;
			}
			else
			{
				//Пытаемся найти персонажа:
				$list = Env::Get()->user->GetAccount()->GetCharacters();
				$character = isset($data['isName']) ? $list->FindByName($character) : $list->Find($character);
				if (empty($character))
				{
					throw new Exception_User_Operation_Character_NotFound();
				}
				
				//Персонаж должен быть оффлайн:
				if (empty($data['canBeOnline']) && $character->IsOnline())
				{
					throw new Exception_User_Operation_Character_Online();
				}
				
				//Персонаж не должен быть забанен:
				if ($character->GetAccount()->IsBanned() && empty($data['canBeBanned']))
				{
					throw new Exception_User_Operation_Character_Banned();
				}
				
				//Обязан ли персонаж принадлежать аккаунту?
				if (isset($data['mustBelong']) && Env::Get()->user->GetAccount()->GetCharacters()->Has($character->GetGuid()) == false)
				{
					throw new Exception_User_Operation_Character_NotBelong();
				}
				
				//Обязан ли персонаж не принадлежать аккаунту?
				if (isset($data['mustNotBelong']) && Env::Get()->user->GetAccount()->GetCharacters()->Has($character->GetGuid()))
				{
					throw new Exception_User_Operation_Character_Belong();
				}
			}
			
			$this->_characters[$name] = $character;
		}
	}
	
	/**
	 * Проверяет, указана ли почта, если действие потребует подтверждения.
	 */
	protected function _CheckMail()
	{
		//Выходим, если подтверждение не нужно:
		if ($this->IsMailConfirmRequired() == false)
		{
			return;
		}

		//Проверяем адрес:
		$mail = $this->_GetMailConfirmReciever()->GetEmail();
		if (empty($mail) || Util_String::IsEmail($mail) == false)
		{
			//Если адрес не указан либо указан некорректно, бросаем ошибку:
			throw new Exception_User_Operation_BadEmail($mail);
		}
	}
	
	/**
	 * Возвращает сумму оплаты для данной операции.
	 * @return float
	 */
	public function GetPrice() {
		try {
			$paramName = sprintf('operations/%s/%s/cheques', $this->_operation->GetName(), $this->_name);
			return Env::Get()->config->Get($paramName);
		} catch (Exception_Config_Base $e) {
			return null;
		}
	}

	/**
	 * Возвращает сумму оплаты в золотых для данной операции.
	 * @return float
	 */
	public function GetPriceOfGold() {
		try {
			$paramName = sprintf('operations/%s/%s/gold', $this->_operation->GetName(), $this->_name);
			return Env::Get()->config->Get($paramName);
		} catch (Exception_Config_Base $e) {
			return null;
		}
	}
	
	/**
	 * Проверяет, достаточно ли на аккаунте чеков.
	 */
	protected function _CheckCheques()
	{
		$price = $this->GetPrice();
		if (empty($price))
		{
			return;
		}

		if (Env::Get()->user->GetCash()->Get() < $price)
		{
			throw new Exception_User_Operation_NotEnoughCheques();
		}
	}

	/**
	 * Проверяет, достаточно ли у персонажа золота.
	 */
	protected function _CheckGold() {
		$price = $this->GetPriceOfGold();
		if (empty($price)) {
			return;
		}
		if ($this->GetCharacter()->GetMoney() < $price) {
			throw new Exception_User_Operation_Character_NotEnoughGold();
		}
	}
	
	/**
	 * Проверяет дополнительные условия.
	 * Все проверки в дочерних классах должны быть определены в этом методе.
	 */
	protected function _CheckAdditionalConditions()
	{
		
	}
	
	/**
	 * Забирает платеж.
	 */
	protected function _TakeOffPayment()
	{
		$price = $this->GetPrice();
		if (empty($price))
		{
			return;
		}
		
		Env::Get()->user->GetCash()->Change(-abs($price), 'operation_payment');
	}

	/**
	 * Забирает золото.
	 */
	protected function _TakeOffGold() {
		$price = $this->GetPriceOfGold();
		if (empty($price)) {
			return;
		}
		$this->GetCharacter()->SetMoney(-abs($price));
	}

	/**
	 * Нужно ли писать историю?
	 * @return boolean
	 */
	protected function _IsHistoryNeeded()
	{
		return true;
	}
	
	/**
	 * Добавляет запись об операции в лог.
	 */
	protected function _WriteHistory()
	{
		//Если история не нужна, выходим:
		if ($this->_IsHistoryNeeded() == false)
		{
			return;
		}
		
		$db = Env::Get()->db->Get('game');

		//Добавляем основную запись:
		$db->Query('
			INSERT INTO #site.site_operation_history (operation, action, account, ip)
			VALUES (:operation, :action, :account, :ip)
		', array(
			'operation' => array('s', $this->_operation->GetName()),
			'action' => array('s', $this->_name),
			'account' => array('d', Env::Get()->user->GetAccount()->GetId()),
			'ip' => array('s', Env::Get()->request->GetIp()),
		));
		
		$recordId = $db->GetLastId();
		
		//Дописываем дополнительную информацию:
		//Поля:
		foreach ($this->_plain as $name => $value)
		{
			if (empty($this->_fields[$name]['noHistory']))
			{
				$db->Query('
					INSERT INTO #site.site_operation_history_plain (history_id, name, value)
					VALUES (:id, :name, :value)
				', array(
					'id' => array('d', $recordId),
					'name' => array('s', $name),
					'value' => array('s', is_array($value) ? implode(',', $value) : strval($value)),
				));
			}
		}
		
		//Аккаунты:
		foreach ($this->_accounts as $field => $account)
		{
			$db->Query('
				INSERT INTO #site.site_operation_history_accounts (history_id, field, account_id)
				VALUES (:id, :field, :account)
			', array(
				'id' => array('d', $recordId),
				'field' => array('s', $field),
				'account' => array('d', $account ? $account->GetId() : 0),
			));
		}
		
		//Персонажи:
		foreach ($this->_characters as $field => $character)
		{
			$db->Query('
				INSERT INTO #site.site_operation_history_characters (history_id, field, guid, name)
				VALUES (:id, :field, :guid, :name)
			', array(
				'id' => array('d', $recordId),
				'field' => array('s', $field),
				'guid' => array('d', $character ? $character->GetGuid() : 0),
				'name' => array('s', $character ? $character->GetName() : ''),
			));
		}
		
		//Дополнительные генераторы:
		foreach ($this->_logGenerators as $name => $callback)
		{
			$db->Query('
				INSERT INTO #site.site_operation_history_custom (history_id, name, value)
				VALUES (:id, :name, :value)
			', array(
				'id' => array('d', $recordId),
				'name' => array('s', $name),
				'value' => array('s', call_user_func_array($callback['method'], $callback['params'])),
			));
		}
	}
	
	
	/**
	 * Возвращает аккаунт, на который отправляется письмо с подтверждением.
	 * @return User_Account
	 */
	protected function _GetMailConfirmReciever()
	{
		return Env::Get()->user->GetAccount();
	}
	
	/**
	 * Отправляет по почте запрос на проведение действия.
	 */
	protected function _SendMailConfirm()
	{
		$user = Env::Get()->user->GetAccount();
		
		//Получаем информацию про последний запрос:
		$last = Env::Get()->db->Get('game')->Query('
			SELECT
				omc.id,
				UNIX_TIMESTAMP(omc.created) AS time,
				omc.status
			FROM #site.site_operation_mail_confirm AS omc
			WHERE TRUE
				AND omc.operation = :operation
				AND omc.action = :action
				AND (FALSE
					OR (TRUE
						AND omc.account != 0
						AND omc.account = :account
					)
					OR (TRUE
						AND omc.account = 0
						AND omc.ip = :ip
					)
				)
			ORDER BY omc.id DESC
			LIMIT 1
		', array(
			'operation' => array('s', $this->_operation->GetName()),
			'action' => array('s', $this->_name),
			'account' => array('d', $user->GetId()),
			'ip' => array('s', Env::Get()->request->GetIp()),
		))->FetchRow();
		
		if ($last)
		{
			//Проверяем время:
			if (time() - $last['time'] < self::TIME_BETWEEN_CONFIRMS)
			{
				throw new Exception_User_Operation_BadCondition('Вам нужно подождать полчаса с момента последней операции');
			}
			
			//Если предыдущий запрос не был завершен, ставим ему соответствующий статус:
			if ($last['status'] == 0)
			{
				Env::Get()->db->Get('game')->Query('
					UPDATE #site.site_operation_mail_confirm
					SET status = 2
					WHERE id = :id
				', array(
					'id' => array('d', $last['id'])
				));
			}
		}
		
		//Добавляем:
		$hash = md5($user->GetId().$this->_operation->GetName().$this->_name.microtime(true));
		Env::Get()->db->Get('game')->Query('
			INSERT INTO #site.site_operation_mail_confirm (operation, action, account, ip, code, data)
			VALUES (:operation, :action, :account, :ip, :code, :data)
		', array(
			'operation' => array('s', $this->_operation->GetName()),
			'action' => array('s', $this->_name),
			'account' => array('d', $user->GetId()),
			'ip' => array('s', Env::Get()->request->GetIp()),
			'code' => array('s', $hash),
			'data' => array('s', serialize($this->_GetInput()))
		));
		
		//Отсылаем письмо:
		$receiver = $this->_GetMailConfirmReciever();
		$receiver->SendMail($this->_GetSubjectForMailConfirm(), 'operation/confirm', array(
			'subject' => $this->_GetSubjectForMailConfirm(),
			'code' => $hash
		));
	}
	
	
	/**
	 * Запускает все проверки и ассоциированное действие.
	 * Флаг сигнализирует о режиме работы (запускается ли действие в первый раз
	 * или уже после подтверждения).
	 * @param bool $isConfirmed
	 */
	public function Run($isConfirmed = false)
	{
		$this->_CheckPlainFields();
		$this->_CheckAccounts();
		$this->_CheckCharacters();
		$this->_CheckMail();
		$this->_CheckCheques();
		$this->_CheckGold();
		$this->_CheckAdditionalConditions();
		
		$this->_isConfirmed = $isConfirmed;
		if ($isConfirmed || $this->isMailConfirmRequired() == false)
		{
			//Подтверждение получено либо не требуется вовсе:
			$this->_TakeOffPayment();
			$this->_TakeOffGold();
			$this->_DoSomeActions();
			$this->_WriteHistory();
		}
		else
		{
			$this->_SendMailConfirm();
			
			$msgs = array('письмо с подтверждением отправлено');
			if ($this->_GetMailConfirmReciever() == Env::Get()->user->GetAccount())
			{
				$msgs[] = 'если письмо идет слишком долго, проверьте адрес почты в разделе "Настройки" и попробуйте выполнить операцию снова';
			}
			
			$this->_SetSuccessMessages($msgs);
		}
	}
};
