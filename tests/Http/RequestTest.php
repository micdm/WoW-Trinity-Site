<?php

class Http_RequestTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Проверяем все функции запроса.
	 */
	public function testVars()
	{
		$_POST = array(
			'foo1' => 'bar1',
			'baz1' => 1,
			'bad' => '<test>'
		);
		
		$_SERVER['REQUEST_URI'] = '/test/?foo2=bar2&baz2=2';
		
		$request = Env::Get()->request;
		
		//Проверяем URL:
		$this->assertEquals('/test/', $request->GetUrl());
		
		//Проверяем одну переменную GET:
		$this->assertEquals('bar2', $request->Get('foo2'));
		
		//Проверяем все переменные GET:
		$this->assertEquals(array(
			'foo2' => 'bar2',
			'baz2' => 2
		), $request->Get());
		
		//Проверяем отсутствующую переменную GET:
		$this->assertEquals(null, $request->Get('wtf'));
		
		//Проверяем одну переменную POST:
		$this->assertEquals('bar1', $request->Post('foo1'));
		
		//Проверяем все переменные POST:
		$this->assertEquals($_POST, $request->Post());
		
		//Проверяем отсутствующую переменную POST:
		$this->assertEquals(null, $request->Post('wtf'));
		
		//Проверяем замену на сущности:
		$input = $request->GetInput();
		$this->assertEquals('&lt;test&gt;', $input['bad']);
	}
};
