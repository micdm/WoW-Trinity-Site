<?php

/**
 * Отправка внутриигровой почты.
 * @package User_Character
 * @author Mic, 2010
 */
class User_Character_Mail
{
	/**
	 * Получатель.
	 * @var User_Character
	 */
	protected $_receiver;
	
	/**
	 * Тема письма.
	 * @var string
	 */
	protected $_subject = '';
	
	/**
	 * Тело письма.
	 * @var string
	 */
	protected $_body = '';
	
	/**
	 * Приложенная сумма в медных.
	 * @var integer
	 */
	protected $_money;
	
	/**
	 * Список приложенных предметов.
	 * @var array
	 */
	protected $_items;
	
	/**
	 * Отсылает письмо через таблицы mail_external*.
	 */
	protected function _SendViaExternalMail()
	{
		$db = Env::Get()->db->Get('game');
		
		$db->Begin();
		try
		{
			$db->Query('
				INSERT INTO mail_external (receiver, subject, message, money)
				VALUES (:receiver, :subject, :message, :money)
			', array(
				'receiver' => array('d', $this->_receiver->GetGuid()),
				'subject' => array('s', $this->_subject),
				'message' => array('s', $this->_body),
				'money' => array('d', $this->_money)
			));
			
			//Добавляем предметы:
			if (count($this->_items))
			{
				//Идентификтор письма:
				$mail = $db->GetLastId();
				foreach ($this->_items as $item)
				{
					$db->Query('
						INSERT INTO mail_external_items (mail_id, item)
						VALUES (:mail, :item)
					', array(
						'mail' => array('d', $mail),
						'item' => array('d', $item)
					));
				}
			}
			
			$db->Commit();
		}
		catch (Exception $e)
		{
			$db->Rollback();
			throw $e;
		}
	}
	
	/**
	 * Отправляет письмо через Trinity-консоль.
	 */
	protected function _SendViaConsole()
	{
		if (Env::Get()->debug->IsTesting())
		{
			return;
		}

		//Базовые аргументы:
		$args = array($this->_receiver->GetName(), '"'.$this->_subject.'"', '"'.$this->_body.'"');
		
		//Добавляем аргументы в зависимости от типа письма:
		if ($this->_money)
		{
			$command = 'SendMoney';
			$args[] = $this->_money;
		}
		else if ($this->_items)
		{
			$command = 'SendItems';
			$args = array_merge($args, $this->_items);
		}
		else
		{
			$command = 'SendMail';
		}
		
		//Добавляем в аргументы саму команду:
		array_unshift($args, $command);
		
		try
		{
			//Выполняем:
			call_user_func_array(array('Third_TrinityConsole', 'DoCommand'), $args);
		}
		catch (Exception_Third_TrinityConsole_Base $e)
		{
			throw new Exception_User_Character_Mail_CanNotSend('проблемы с консолью: '.$e->getMessage());
		}
	}
	
	/**
	 * Задает получателя.
	 * @param User_Character $value
	 * @return User_Character_Mail
	 */
	public function SetReceiver(User_Character $value)
	{
		$this->_receiver = $value;
		return $this;
	}
	
	/**
	 * Задает тему письма.
	 * @param string $value
	 * @return User_Character_Mail
	 */
	public function SetSubject($value)
	{
		$this->_subject = $value;
		return $this;
	}
	
	/**
	 * Задает тело письма.
	 * @param string $value
	 * @return User_Character_Mail
	 */
	public function SetBody($value)
	{
		$this->_body = $value;
		return $this;
	}
	
	/**
	 * Задает приложенную сумму.
	 * @param integer $value
	 * @return User_Character_Mail
	 */
	public function SetMoney($value)
	{
		$this->_money = $value;
		return $this;
	}
	
	/**
	 * Задает список приложенных предметов.
	 * @param array $list
	 * @return User_Character_Mail
	 */
	public function SetItems($list)
	{
		$this->_items = (array)$list;
		return $this;
	}
	
	/**
	 * Отправляет персонажу письмо в игру.
	 */
	public function Send()
	{
		$section = md5(microtime(true));
		Dev_Debug_Section::Begin($section);
		
		//При тестировании не будем слать:
		if (Env::Get()->debug->IsTesting())
		{
			return;
		}
		
		$method = Env::Get()->config->Get('gameMailMethod');
		if ($method == 'console')
		{
			$this->_SendViaConsole();
		}
		else if ($method == 'external')
		{
			$this->_SendViaExternalMail();
		}
		
		Dev_Debug_Section::End();
	}
};
