<?php

/**
 * Загрузчик юнит-тестов (с автозагрузкой и всеми делами).
 * @author Mic, 2010
 */
class TestLoader implements PHPUnit_Runner_TestSuiteLoader
{
    public function load($suiteClassName, $suiteClassFile = '')
    {
    	//Злой хак :(
    	include_once(basename(__FILE__).'/../../lib/engine/_handler_dev.php');
    	return new ReflectionClass($suiteClassName);
    }

    public function reload(ReflectionClass $aClass)
    {
    	return $aClass;
    }
};
