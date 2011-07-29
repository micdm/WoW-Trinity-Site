<?php

class Cache_ManagerTest extends PHPUnit_Framework_TestCase
{
	public function getSomeValues()
	{
		return array(
			array(1),
			array('testtest'),
			array(array(1,2,3)),
			array(new stdClass())
		);
	}
	
	/**
	 * Проверяем сохранение-загрузку.
	 * @dataProvider getSomeValues
	 */
	public function testSaveLoad($value)
	{
		Env::Get()->cache->Save('testkey', $value);
		$this->assertEquals($value, Env::Get()->cache->Load('testkey'));
	}
	
	/**
	 * Проверяем сохранение-очистку.
	 */
	public function testSaveClear()
	{
		$cache = Env::Get()->cache;
		
		$cache->Save('testkey', 'foo');
		$cache->Clear('testkey');
		$this->assertNull($cache->Load('testkey'));
	}
	
	/**
	 * Проверяем сохранение с пустым ключом.
	 * @expectedException Exception_Cache_EmptyKey
	 */
	public function testSaveWithEmptyKey()
	{
		Env::Get()->cache->Save('', 'foo');
	}
	
	/**
	 * Проверяем двойной сохранение без указания ключа.
	 * @expectedException Exception_Cache_EmptyKey
	 */
	public function testDuplicateSaving()
	{
		$cache = Env::Get()->cache;
		
		$cache->Load('testkey');
		$cache->Save(null, 'foo');
		$cache->Save(null, 'bar');
	}
};
