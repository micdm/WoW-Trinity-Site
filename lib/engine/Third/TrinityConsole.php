<?php

/**
 * Удаленная консоль Trinity.
 * @package Third
 * @author Mic, 2010
 */
class Third_TrinityConsole
{
	/**
	 * Таймаут подключения.
	 * @var integer
	 */
	const TIMEOUT									= 5;
	
	/**
	 * Количество попыток подключиться к серверу.
	 * @var integer
	 */
	const ATTEMPTS									= 5;
	
	/**
	 * Пауза между попытками в секундах.
	 * @var integer
	 */
	const PAUSE										= 2;
	
	/**
	 * 
	 * @var resource
	 */
	private static $_stream;
	
	private static function _DoConnectAttempt()
	{
		$config = Env::Get()->config;
		
		//Пытаемся подключиться:
		$stream = false;
		try
		{
			$stream = fsockopen($config->Get('console/host'), $config->Get('console/port'), $errorNumber, $errorMsg, self::TIMEOUT);
		}
		catch (Exception_Php_Warning $e)
		{

		}
		
		//Не получилось подключиться:
		if ($stream == false)
		{
			throw new Exception_Third_TrinityConsole_NoConnect($errorMsg);
		}
		
		$greeting = fread($stream, 1024);
		if (strpos($greeting, 'I\'m busy right now, come back later') === 0)
		{
			//Сервер занят:
			throw new Exception_Third_TrinityConsole_ServerBusy('сервер занят');
		}
		else if (strpos($greeting, 'Authentication required') !== 0)
		{
			//Пришел неожиданный ответ:
			throw new Exception_Third_TrinityConsole_BadGreeting('получено приветствие: "'.$greeting.'"');
		}
		
		self::$_stream = $stream;
	}
	
	/**
	 * Пишет в поток и ждет немного времени.
	 * @param string $string
	 */
	private static function _Write($string)
	{
		fwrite(self::$_stream, $string.PHP_EOL);
	}
	
	/**
	 * Авторизует в консоли.
	 */
	private static function _Authorize()
	{
		$config = Env::Get()->config;

		self::_Write($config->Get('console/user'));
		self::_Write($config->Get('console/password'));
		
		$status = fgets(self::$_stream);
		if (strpos($status, 'Welcome to a') === false)
		{
			throw new Exception_Third_TrinityConsole_AuthError($status);
		}
	}
	
	/**
	 * Подключается к удаленному серверу.
	 */
	private static function _Connect()
	{
		//Выходим, если уже подключены:
		if (self::$_stream !== null)
		{
			return;
		}

		try
		{
			//Делаем несколько попыток подключиться:
			for ($i = 0; $i < self::ATTEMPTS; $i += 1)
			{
				try
				{
					self::_DoConnectAttempt();
					break;
				}
				catch (Exception_Third_TrinityConsole_ServerBusy $e)
				{
					//Если сервер занят:
					if ($i != self::ATTEMPTS - 1)
					{
						//Делаем паузу и пытаемся снова:
						sleep(self::PAUSE);
					}
					else
					{
						//Сообщаем, что сервер занят долго:
						throw new Exception_Third_TrinityConsole_ServerBusyPermanently('консоль занята кем-то другим');
					}
				}
			}
			
			//Авторизуемся:
			self::_Authorize();
		}
		catch (Exception_Third_TrinityConsole_Base $e)
		{
			self::_Close();
			throw $e;
		}
	}
	
	/**
	 * Закрывает соединение.
	 */
	private static function _Close()
	{
		if (self::$_stream)
		{
			fclose(self::$_stream);
			self::$_stream = null;
		}
	}
	
	/**
	 * Пишет команду в консоль и возвращает ответ.
	 * @param string $string
	 * @return string
	 */
	public static function Send($string)
	{
		self::_Connect();

		//Отправляем команду и получаем ответ (по пути убираем все лишнее):
		self::_Write($string);
		$result = trim(str_replace('TC>', '', fgets(self::$_stream)));
		
		//Тут можно было бы закрыть соединение.
		//Но не будем, так как в некоторых местах (массовой отправки почты, например) выполняется подряд много
		//команд, а в какую-нибудь очередь класть их неохота.
		//self::_Close();
		
		return $result;
	}
	
	/**
	 * Выполняет команду и проверяет ответ.
	 * @param string $command
	 */
	public static function DoCommand($command)
	{
		$args = func_get_args();
		if ($command == 'CharacterRename')
		{
			//Смена имени:
			$command = '.character rename '.$args[1];
			$response = 'Forced rename for player '.$args[1].' \(GUID \#\d+\) will be requested at next login\.';
		}
		else if ($command == 'CharacterCustomize')
		{
			//Смена внешности:
			$command = '.character customize '.$args[1];
			$response = 'Forced customize for player '.$args[1].' \(GUID \#\d+\) will be requested at next login\.';
		}
		else if ($command == 'SendMail')
		{
			//Отправка простого письма:
			$command = '.send mail '.implode(' ', array_slice($args, 1, 3));
			$response = 'Mail sent to '.$args[1];
		}
		else if ($command == 'SendMoney')
		{
			//Отправка денег:
			$command = '.send money '.implode(' ', array_slice($args, 1, 4));
			$response = 'Mail sent to '.$args[1];
		}
		else if ($command == 'SendItems')
		{
			//Отправка предметов:
			$command = '.send items '.implode(' ', array_slice($args, 1));
			$response = 'Mail sent to '.$args[1];
		}
		
		$result = self::Send($command);
		//if (preg_match('#^'.$response.'$#', $result) == false)
		//{
		//	throw new Exception_Third_TrinityConsole_BadResponse($result);
		//}
	}
};
