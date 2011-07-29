<?php

/**
 * Рассылка писем.
 * @author Mic, 2010
 * @package Util
 */
class Util_Mail
{
	/**
	 * Объект для отсылки почты.
	 * @var PHPMailer
	 */
	private static $_mailer;
	
	/**
	 * Инициализирует почтальона.
	 */
	private static function _Init()
	{
		if (self::$_mailer)
		{
			return;
		}

		$mailer = new PHPMailer();
		$mailer->set('CharSet', 'utf8');
		
		//Заполняем отправителя:
		$from = Env::Get()->config->Get('mail/from');
		$mailer->SetFrom($from['address'], $from['name']);

		//Настраиваем SMTP, если нужно:
		if (Env::Get()->config->Get('mail/method') == 'smtp')
		{
			//Настройки SMTP:
			$mailer->IsSMTP();
			$mailer->SMTPAuth = true;

			//Информация о почтовом сервере:
			$info = array(
				'Host' => 'smtp/host',
				'Port' => 'smtp/port',
				'Username' => 'smtp/username',
				'Password' => 'smtp/password',
			);
			
			//Донастраиваем:
			foreach ($info as $field => $value)
			{
				$mailer->set($field, Env::Get()->config->Get('mail/'.$value));
			}
		}

		self::$_mailer = $mailer;
	}
	
	/**
	 * Назначает переменные.
	 * @param array $vars
	 */
	private static function _AssignVars($vars)
	{
		if ($vars)
		{
			$c = Tpl_Context::Factory();
			foreach ($vars as $name => $value)
			{
				$c->Set($name, $value);
			}
			
			Tpl_Smarty_Wrapper::Assign($c);
		}
	}
	
	/**
	 * Заполняет письмо текстом.
	 * @param string $subject
	 * @param string $path
	 */
	private static function _ComposeMail($subject, $path)
	{
		self::$_mailer->set('Subject', $subject);
		self::$_mailer->set('Body', Tpl_Smarty_Wrapper::Fetch('mail/'.$path.'.txt'));
	}
	
	/**
	 * Отправляет письмо.
	 * @param string $address
	 * @param string $title
	 * @param string $path
	 * @param array $vars
	 */
	public static function Send($address, $subject, $path, $vars = null)
	{
		ob_start();
		
		try
		{
			//Инициализируем:
			self::_Init();
			
			//Добавляем получателя:
			self::$_mailer->AddAddress($address);
			
			//Назначаем переменные:
			self::_AssignVars($vars);
			
			//Заполняем письмо:
			self::_ComposeMail($subject, $path);
			
			//Восстанавливаем контекст:
			Tpl_Smarty_Wrapper::Restore();
	
			//Пробуем отослать письмо:
			$result = null;
			if (Env::Get()->debug->IsTesting())
			{
				$result = self::$_mailer->Body;
			}
			else if (self::$_mailer->Send() == false)
			{
				//Отослать не получилось:
				throw new Exception_Mail(self::$_mailer->ErrorInfo);
			}
		}
		catch (Exception $e)
		{
			ob_end_clean();
			throw $e;
		}
		
		ob_end_clean();
		return $result;
	}
};
