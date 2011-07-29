<?php

class Dev_LoggerTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		Env::Get()->db->Get('game')->Begin();
	}
	
	public function tearDown()
	{
		Env::Get()->db->Get('game')->Rollback();
	}

	/**
	 * Тестируем нормальное поведение.
	 */
	public function testNormal()
	{
		Env::Get()->log->Add('test', array('test data', 123));
		
		//Проверяем, что запись появилась в таблице:
		$count = Env::Get()->db->Get('game')->Query('
			SELECT
				COUNT(*)
			FROM #site.site_log_test
		')->FetchOne();
		
		$this->assertEquals(1, $count);
	}
	
	/**
	 * Тестируем нормальное поведение с записью в файл.
	 */
	public function testNormalWithFile()
	{
		$file = Dev_Logger::GetLogFilename('test');
		
		//Если файл существует, удаляем:
		if (file_exists($file))
		{
			unlink($file);
		}
		
		Env::Get()->log->Add('test', array('test data', 123), 'file');
		
		//Проверяем, что файл создался:
		$this->assertTrue(file_exists($file));
		
		//Проверяем, что не пустой:
		$this->assertNotEquals(0, strlen(file_get_contents($file)));
		
		//Удаляем файл:
		unlink($file);
	}
	
	/**
	 * Тестируем ситуацию, когда лог не существует.
	 * @expectedException Exception_Log_NotFound
	 */
	public function testIfLogDoesntExist()
	{
		Env::Get()->log->Add('another_test', array('test data', 123));
	}
};
