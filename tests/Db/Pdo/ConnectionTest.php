<?php

class Db_Pdo_ConnectionTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Добавляет тестовую таблицу.
	 */
	private static function _CreateTable()
	{
		Env::Get()->db->Get('game')->Query('
			CREATE TABLE #site.test (
				id INT NOT NULL ,
				PRIMARY KEY (id)
			) ENGINE = InnoDB
		');
	}
	
	/**
	 * Удаляет таблицу.
	 */
	private static function _DropTable()
	{
		Env::Get()->db->Get('game')->Query('
			DROP TABLE #site.test
		');
	}
	
	/**
	 * Добавляет запись.
	 */
	private static function _AddRecord()
	{
		Env::Get()->db->Get('game')->Query('
			INSERT INTO #site.test (id)
			VALUES (1)
		');
	}
	
	/**
	 * Возвращает количество записей в тестовой таблице.
	 */
	private static function _GetRecordsCount()
	{
		return Env::Get()->db->Get('game')->Query('
			SELECT
				COUNT(*)
			FROM #site.test
		')->FetchOne();
	}
	
	public function setUp()
	{
		try
		{
			self::_CreateTable();
		}
		catch (Exception_Db_Query_TableExists $e)
		{
			//Пересоздаем таблицу, если она уже есть (чтоб была пустая):
			self::_DropTable();
			self::_CreateTable();
		}
	}
	
	public function tearDown()
	{
		try
		{
			self::_DropTable();
		}
		catch (Exception_Db_Query_TableNotFound $e)
		{
			
		}
	}
	
	/**
	 * Тестируем ситуацию, когда нельзя подключиться к базе.
	 * @expectedException Exception_Db_Connect
	 */
	public function testBadConnection()
	{
		$connection = Db_Pdo_Connection::Factory('mysql:host=localhost;port=3306;dbname=test', 'foo', 'bar');
		$connection->Query('SHOW TABLES');
	}
	
	/**
	 * Тестируем создание новой таблицы, если такая же уже есть.
	 * @expectedException Exception_Db_Query_TableExists
	 */
	public function testCreateTableIfExists()
	{
		$this->_CreateTable();
	}
	
	/**
	 * Тестируем удаление таблицы, которой не существует.
	 * @expectedException Exception_Db_Query_TableNotFound
	 */
	public function testDropTableIfNotFound()
	{
		$this->_DropTable();
		$this->_DropTable();
	}
	
	/**
	 * Тестируем добавление новой записи, которая не попадает в ключ.
	 * @expectedException Exception_Db_Query_ConstraintViolation
	 */
	public function testInsertIfKeyExists()
	{
		self::_AddRecord();
		self::_AddRecord();
	}
	
	/**
	 * Тестируем подтверждение транзакции.
	 */
	public function testCommit()
	{
		$db = Env::Get()->db->Get('game');
		$db->Begin();
		self::_AddRecord();
		$db->Commit();

		$this->assertEquals(1, self::_GetRecordsCount());
	}
	
	/**
	 * Тестируем откат транзакции.
	 */
	public function testRollback()
	{
		$db = Env::Get()->db->Get('game');
		$db->Begin();
		self::_AddRecord();
		$db->Rollback();
		
		$this->assertEquals(0, self::_GetRecordsCount());
	}
};
