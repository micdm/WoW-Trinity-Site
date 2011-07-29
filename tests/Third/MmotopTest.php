<?php

class Third_MmotopTest extends PHPUnit_Framework_TestCase
{
	public function testGetServerStats()
	{
		$stats = Third_Mmotop::GetServerStats();
		
		$this->assertArrayHasKey('place', $stats);
		$this->assertTrue(is_numeric($stats['place']));
		
		$this->assertArrayHasKey('votes', $stats);
		$this->assertTrue(is_numeric($stats['votes']));
	}
};
