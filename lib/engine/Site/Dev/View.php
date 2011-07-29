<?php

/**
 * Отладочный раздел.
 * @author Mic, 2010
 */
class Site_Dev_View extends View
{
	/**
	 * Включает/выключает один из режимов отладки.
	 * @param string $mode
	 */
	protected function _ToggleMode($mode)
	{
		$needEnable = Env::Get()->request->Get($mode);
		if ($needEnable !== null)
		{
			Util_Cookie::Set($mode, $needEnable ? Env::Get()->debug->GetCookieValue() : '', 0, '/');
			return 'Режим '.$mode.' '.($needEnable ? 'включен' : 'отключен');
		}
		
		return null;
	}
	
	public function Index($args, $params)
	{
		foreach (array('debug', 'no_cache') as $mode)
		{
			$result = $this->_ToggleMode($mode);
			if ($result)
			{
				return $result;
			}
		}
		
		throw new Exception_Http_NotFound();
	}
	
	public function Tests($args, $params)
	{
		if (Env::Get()->debug->IsActive())
		{
			if (Env::Get()->request->Get('tests'))
			{
				//Запускаем тесты:
				$this->_context->Set('result', Dev_TestRunner::Run(Env::Get()->request->Get('tests')));
			}
			else
			{
				//Рисуем список тестов:
				$this->_context->Set('tests', Dev_TestRunner::LoadTestList());
			}
	
			return $this->_Render('dev/tests.htm');
		}
		else
		{
			throw new Exception_Http_NotFound();
		}
	}
};
