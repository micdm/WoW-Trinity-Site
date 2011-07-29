<?php

/**
 * Логирование разнообразных событий.
 * @package Dev
 * @author Mic, 2010
 */
class Dev_Logger
{
	/**
	 * Добавляет запись в БД.
	 * @param string $log
	 * @param string $ip
	 * @param integer $user
	 * @param mixed $data
	 */
	private static function _WriteIntoDb($log, $ip, $user, $data)
	{
		try
		{
			Env::Get()->db->Get('game')->Query('
				INSERT INTO #site.site_log_'.$log.' (ip, user, data)
				VALUES (:ip, :user, :data)
			', array(
				'ip' => array('s', $ip),
				'user' => array('d', $user),
				'data' => array('s', $data),
			));
		}
		catch (Exception_Db_Query_TableNotFound $e)
		{
			//Если таблица не найдена, значит, и писать некуда:
			throw new Exception_Log_NotFound();
		}
		catch (Exception $e)
		{
			//При ошибке пытаемся записать хотя бы в файл:
			self::_WriteIntoFile($log, $ip, $user, $data);
		}
	}

	/**
	 * Добавляет запись в файл.
	 * @param string $log
	 * @param string $ip
	 * @param integer $user
	 * @param mixed $data
	 */
	private static function _WriteIntoFile($log, $ip, $user, $data)
	{
		$chunks = array();
		$chunks[] = '['.date('H:i:s d.m.Y').'] ['.$ip.'] ['.$user.']';
		$chunks[] = $data; 
		
		file_put_contents(self::GetLogFilename($log), implode(PHP_EOL, $chunks).PHP_EOL.PHP_EOL, FILE_APPEND);
	}
	
	/**
	 * Возвращает имя файла, в который будет записан лог.
	 * @param string $log
	 */
	public static function GetLogFilename($log)
	{
		return LOGS_ROOT.$log.'.log';
	}
	
	/**
	 * Добавляет в указанный лог новую запись.
	 * @param string $log
	 * @param mixed $data
	 * @param string $type
	 */
	public function Add($log, $data, $type = 'db')
	{
		//Писать можно в разные хранилища:
		switch ($type)
		{
			case 'db':
				$method = '_WriteIntoDb';
				break;
				
			case 'file':
				$method = '_WriteIntoFile';
				break;
		}
		
		call_user_func(array(__CLASS__, $method), $log, Env::Get()->request->GetIp(), Env::Get()->user->GetAccount()->GetId(), serialize($data));
	}
};
