<?php

/**
 * Базовый класс всех операций.
 * @package User_Operation
 * @author Mic, 2010
 */
abstract class User_Operation_Base
{
	const ERROR_BAD_EMAIL = 1;
	
	/**
	 * Название операции. Используется для записи в лог.
	 * @var string
	 */
	protected $_name;
	
	/**
	 * Объекты действий.
	 * @var array
	 */
	protected $_actions;
	
	/**
	 * Название текущего действия.
	 * @var string
	 */
	protected $_current;
	
	/**
	 * Введенные пользователем данные, восстановленные после
	 * успешного подтверждения по почте.
	 * @var array
	 */
	protected $_input;
	
	/**
	 * @param string $operation
	 * @return User_Operation_Base
	 */
	public static function Factory($operation)
	{
		//Переводим первые буквы в верхний регистр:
		$operation = implode('_', array_map('ucfirst', explode('_', $operation)));
		
		$className = 'User_Operation_'.ucfirst($operation);
		return new $className($operation);
	}
	
	/**
	 * @param string $name
	 */
	public function __construct($name)
	{
		$this->_name = strtolower($name);
		
		$this->_Setup();
	}

	/**
	 * Использовать ли GET-переменные вместо POST-переменных?
	 * @return boolean
	 */
	protected function _IsUsingGetMethod()
	{
		return false;
	}
	
	/**
	 * Описывает действия в рамках данной операции.
	 */
	protected function _Setup()
	{
		//Добавляем основное действие по умолчанию:
		$this->_AddAction('main', ucfirst($this->_name).'_Main');
	}
	
	/**
	 * Возвращает имя операции.
	 * @return string
	 */
	public function GetName()
	{
		return $this->_name;
	}
	
	/**
	 * Возвращает описание действия для истории.
	 * @param string $action
	 * @param array $accounts
	 * @param array $characters
	 * @param array $plain
	 * @param array $custom
	 * @return string
	 */
	public function GetDescription($action, $accounts, $characters, $plain, $custom)
	{
		if (empty($this->_actions[$action]))
		{
			return 'Описание не найдено :(';
		}
		
		return $this->_actions[$action]->GetDescription($accounts, $characters, $plain, $custom);
	}
	
	/**
	 * Возвращает значение указанного поля.
	 * @param string $name
	 * @return string
	 */
	public function GetFieldValue($name)
	{
		if ($this->_input)
		{
			return $this->_input[$name];
		}
		else
		{
			return $this->_IsUsingGetMethod() ? Env::Get()->request->Get($name) : Env::Get()->request->Post($name);
		}
	}
	
	/**
	 * Возвращает имя текущего действия.
	 * @return string
	 */
	public function GetCurrentAction()
	{
		return $this->_current;
	}
	
	/**
	 * Возвращает сообщение(я) про успешный исход для текущего действия.
	 * @return mixed
	 */
	public function GetSuccessMessages()
	{
		return $this->_actions[$this->_current]->GetSuccessMessages();
	}
	
	/**
	 * 
	 * @param string $name
	 * @param string $classNamePart
	 * @return User_Operation_Base
	 */
	protected function _AddAction($name, $classNamePart)
	{
		//Ищем в той же директории:
		$prefix = substr(get_class($this), 0, strrpos(get_class($this), '_'));
		$className = $prefix.'_Action_'.$classNamePart;
		
		$action = new $className($this, $name);
		$this->_actions[$name] = $action;
		
		return $this;
	}

	/**
	 * Проверяет, нужно ли подтверждение для любого из действий.
	 * @return bool;
	 */
	protected function _IsConfirmForAnyActionRequired()
	{
		foreach ($this->_actions as $action)
		{
			if ($action->IsMailConfirmRequired())
			{
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Проверяет, указан ли код для подтверждения операции.
	 * @return string
	 */
	protected function _GetActionForMailConfirm()
	{
		$code = Env::Get()->request->Get('code');
		if ($code && $this->_IsConfirmForAnyActionRequired())
		{
			//Загружаем из базы информацию про подтверждение:
			$confirm = Env::Get()->db->Get('game')->Query('
				SELECT
					id,
					action,
					data
				FROM #site.site_operation_mail_confirm
				WHERE TRUE
					AND code = :code
					AND operation = :operation
					AND account = :account
					AND status = 0
			', array(
				'code' => array('s', $code),
				'operation' => array('s', $this->GetName()),
				'account' => array('d', Env::Get()->user->GetAccount()->GetId())
			))->FetchRow();

			//Что-то нашлось:
			if ($confirm)
			{
				//Обновляем информацию о подтверждении в базе:
				Env::Get()->db->Get('game')->Query('
					UPDATE #site.site_operation_mail_confirm
					SET status = 1
					WHERE id = :id
				', array(
					'id' => array('d', $confirm['id']),
				));
				
				//Сохраняем ранее указанные пользователем входные данные:
				$this->_input = unserialize($confirm['data']);
				
				return $confirm['action'];
			}
		}
		
		return null;
	}
	
	/**
	 * Определяет текущую операцию по содержимому POST.
	 * @return string
	 */
	protected function _DetectCurrentAction()
	{
		foreach ($this->_actions as $name => $action)
		{
			//Ищем переменную с именем как у действия:
			$var = $this->_IsUsingGetMethod() ? Env::Get()->request->Get($name) : Env::Get()->request->Post($name);
			if ($var)
			{
				return $name;
			}
		}
		
		return null;
	}
	
	/**
	 * Просто запускает операцию.
	 */
	protected function _Run()
	{
		$action = $this->_GetActionForMailConfirm();
		
		try
		{
			$db = Env::Get()->db->Get('game');
			$db->Begin();
			
			if ($action)
			{
				//Действие по коду подтверждения найдено, запускаем:
				$this->_current = $action;
				$this->_actions[$action]->Run(true);
			}
			else
			{
				//Определяем текущее действие:
				$action = $this->_DetectCurrentAction();
				if ($action)
				{
					$this->_current = $action;
					$this->_actions[$action]->Run();
				}
			}

			$db->Commit();
		}
		catch (Exception $e)
		{
			$db->Rollback();
			throw $e;
		}

		//Если действие выполнено, обновляем страницу:
		if ($action && $this->_actions[$action]->IsRedirectRequired())
		{
			throw new Exception_Http_Redirected();
		}
	}
	
	/**
	 * Запускает операцию на выполнение.
	 * Можно указать, оборачивать ли внутренние исключения в Exception_UserInput (может
	 * быть полезно для отладки).
	 * @param bool $needWrapExceptions
	 */
	public function Run($needWrapExceptions = true)
	{
		if ($needWrapExceptions == false)
		{
			$this->_Run();
		}
		else
		{
			try
			{
				$this->_Run();
			}
			catch (Exception_User_Operation_BadEmail $e)
			{
				if ($e->getMessage())
				{
					$msg = 'укажите корректный адрес почты для Вашего аккаунта в разделе "Настройки"';
				}
				else
				{
					$msg = 'укажите адрес почты для Вашего аккаунта в разделе "Настройки"';
				}
				
				throw new Exception_UserInput($msg, self::ERROR_BAD_EMAIL);
			}
			catch (Exception_User_Operation_PlainField_Need $e)
			{
				throw new Exception_UserInput('заполните все поля');
			}
			catch (Exception_User_Operation_Account_Need $e)
			{
				throw new Exception_UserInput('укажите аккаунт');
			}
			catch (Exception_User_Operation_Account_NotFound $e)
			{
				throw new Exception_UserInput('указанный Вами аккаунт не найден');
			}
			catch (Exception_User_Operation_Account_Current $e)
			{
				throw new Exception_UserInput('Вы не можете указать свой аккаунт');
			}
			catch (Exception_User_Operation_Account_Banned $e)
			{
				throw new Exception_UserInput('указанный Вами аккаунт забанен');
			}
			catch (Exception_User_Operation_Character_Need $e)
			{
				throw new Exception_UserInput('укажите персонажа');
			}
			catch (Exception_User_Operation_Character_NotFound $e)
			{
				throw new Exception_UserInput('указанный Вами персонаж не найден');
			}
			catch (Exception_User_Operation_Character_Online $e)
			{
				throw new Exception_UserInput('Вам нужно выйти указанным персонажем из игрового мира');
			}
			catch (Exception_User_Operation_Character_Banned $e)
			{
				throw new Exception_UserInput('указанный Вами персонаж забанен и не может участвовать в операциях');
			}
			catch (Exception_User_Operation_Character_NotBelong $e)
			{
				throw new Exception_UserInput('Вам нужно указать своего персонажа');
			}
			catch (Exception_User_Operation_Character_Belong $e)
			{
				throw new Exception_UserInput('Вы не можете указать своего персонажа');
			}
			catch (Exception_User_Operation_NotEnoughCheques $e)
			{
				throw new Exception_UserInput('У Вас недостаточно чеков');
			}
			catch (Exception_User_Operation_BadCondition $e)
			{
				throw new Exception_UserInput($e->getMessage());
			}
		}
	}
	
	/**
	 * Возвращает цену совершения действия.
	 * @param string $action
	 * @return float
	 */
	public function GetPrice($action = 'main')
	{
		return $this->_actions[$action]->GetPrice();
	}
};
