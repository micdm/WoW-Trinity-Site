<?php

/**
 * Подключение к БД через PDO.
 * @author Mic, 2010
 */
class Db_Pdo_Connection
{
	/**
	 * Информация для подключения к БД.
	 * @var string
	 */
	private $_dsn;
	
	/**
	 * Имя пользователя для подключения.
	 * @var string
	 */
	private $_user;
	
	/**
	 * Пароль для подключения.
	 * @var string
	 */
	private $_password;
	
	/**
	 * Дополнительные параметры:
	 * @var array
	 */
	private $_extra = array();
	
	/**
	 * Объект PDO для общения с базой.
	 * @var PDO
	 */
	private $_pdo;
	
	/**
	 * Информация обо всех выполненных запросах.
	 * @var array
	 */
	private $_queries;
	
	/**
	 * Уровень вложенности транзакции.
	 * @var integer
	 */
	private $_transactionLevel = 0;
	
	/**
	 * @param $host
	 * @param $user
	 * @param $password
	 * @param $dbName
	 * @param $extra
	 * @return Db_Connection_Mysqli
	 */
	public static function Factory($dsn, $user, $password, $extra = null)
	{
		return new self($dsn, $user, $password, $extra);
	}
	
	public function __construct($dsn, $user, $password, $extra)
	{
		//Загружаем основные параметры:
		$this->_dsn = $dsn;
		$this->_user = $user;
		$this->_password = $password;
		
		//Загружаем экстра-параметры:
		if (isset($extra))
		{
			$this->_extra = $extra;
		}
		
		//Тут пока не подключаемся к БД. Сделаем это при первом запросе.
	}
	
	/**
	 * Инициирует подключение.
	 */
	private function _Init()
	{
		if (empty($this->_pdo))
		{
			try
			{
				//Подключаемся и ловим ошибки:
				$this->_pdo = new PDO($this->_dsn, $this->_user, $this->_password);
				
				//При ошибках пусть кидает исключение:
				$this->_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				
				//Юникод:
				$this->_pdo->query('SET NAMES "utf8"');
			}
			catch (PDOException $e)
			{
				throw new Exception_Db_Connect($e->getMessage());
			}
		}
	}
	
	/**
	 * Подставляет экстра-параметры по шаблону.
	 * @param string $sql
	 * @return string
	 */
	private function _InsertExtraParams($sql)
	{
		foreach ($this->_extra as $field => $value)
		{
			$sql = str_replace('#'.$field, $value, $sql);
		}
		
		return $sql;
	}
	
	/**
	 * Сохраняет информацию о запросе для отладки.
	 * @param string $sql
	 */
	private function _StoreForDebug($sql)
	{
		if (Env::Get()->debug->IsActive())
		{
			$info = array(
				'time' => 0
			);
			
			//Убираем лишние пробелы/табуляцию:
			preg_match('#^\n(\s+)#', $sql, $matches);
			if (isset($matches[1]))
			{
				$sql = str_replace($matches[1], '', $sql);
			}
			
			$info['sql'] = trim($sql);
			$this->_queries[] = $info;
		}
	}

	/**
	 * Выполняет запрос с подстановкой переменных (опционально).
	 * @param string $query
	 * @param array $vars
	 * @return Db_Pdo_Result
	 */
	public function Query($query, $vars = null)
	{
		$sectionName = 'sql'.md5($query.microtime(true));
		Dev_Debug_Section::Begin($sectionName, 'sql-запрос');
		
		try
		{
			$this->_Init();
			
			//Подставляем экстра-параметры:
			$query = $this->_InsertExtraParams($query);
			
			//Сохраняем для отладки:
			$this->_StoreForDebug($query);
			
			//Подготавливаем:
			$statement = $this->_pdo->prepare($query);
			
			//Биндим:
			$params = array();
			if ($vars)
			{
				foreach ($vars as $name => $var)
				{
					$statement->bindParam(':'.$name, $var[1], ($var[0] == 'd') ? PDO::PARAM_INT : PDO::PARAM_STR);
				}
			}
			
			$statement->execute();
		}
		catch (PDOException $e)
		{
			//В зависимости от типа ошибки бросаем исключения разных типов:
			switch ($e->getCode())
			{
				case '23000':
					$class = 'Exception_Db_Query_ConstraintViolation';
					break;
				
				case '42S01':
					$class = 'Exception_Db_Query_TableExists';
					break;
				
				case '42S02':
					$class = 'Exception_Db_Query_TableNotFound';
					break;
					
				default:
					$class = 'Exception_Db_Query_Base';
					break;
			}
			
			throw new $class($e->getMessage().PHP_EOL.$query);
		}
		
		//Получаем результат:
		$result = Db_Pdo_Result::Factory($statement);
		
		Dev_Debug_Section::End($sectionName);
		return $result;
	}
	
	/**
	 * Возвращает идентификатор последней добавленной записи.
	 * @return integer
	 */
	public function GetLastId()
	{
		return $this->_pdo->lastInsertId();
	}
	
	/**
	 * Возвращает информацию об использовании этого соединения:
	 * @return array
	 */
	public function GetDebugInfo()
	{
		return $this->_queries;
	}
	
	/**
	 * Стартует транзакцию.
	 * Транзакции пока работают кривовато: применяется только транзакция
	 * первого уровня вложенности.
	 */
	public function Begin()
	{
		$this->_Init();
		
		if ($this->_transactionLevel == 0)
		{
			$this->_pdo->beginTransaction();
		}
		
		$this->_transactionLevel += 1;
	}
	
	/**
	 * Применяет транзакцию.
	 */
	public function Commit()
	{
		$this->_transactionLevel -= 1;
		if ($this->_transactionLevel == 0)
		{
			$this->_pdo->commit();
		}
	}
	
	/**
	 * Откатывает транзакцию.
	 */
	public function Rollback()
	{
		$this->_transactionLevel -= 1;
		if ($this->_transactionLevel == 0)
		{
			$this->_pdo->rollBack();
		}
	}
};
