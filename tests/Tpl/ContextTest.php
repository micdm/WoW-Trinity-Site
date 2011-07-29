<?php

class Tpl_ContextTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Тестируем нормальное поведение.
	 */
	public function testNormal()
	{
		$c = Tpl_Context::Factory();
		$c->Set('foo', 'bar');
		$vars = $c->GetAll();
		
		$this->assertEquals('bar', $vars['foo']);
	}
	
	/**
	 * Тестируем ситуацию, когда переменная назначается повторно.
	 * @expectedException Exception_Tpl_AlreadyAssigned
	 */
	public function testIfVarAlreadyAssigned()
	{
		$c = Tpl_Context::Factory();
		$c->Set('foo', 'bar');
		$c->Set('foo', 'bar');
	}
};
