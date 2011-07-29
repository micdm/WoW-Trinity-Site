<?php

/**
 * Генерация ответов для разных платежных систем.
 * @author Mic, 2010
 */
class Site_Gates_View extends View
{
	public function Sms($args, $params)
	{
		try
		{
			User_Donating_Sms::Run();
			$status = 'чеки http://aeternus.ru начислены, спасибо';
		}
		catch (Exception_Donate_Sms $e)
		{
			$status = $e->getMessage();
		}
		
		return 'status: reply'.PHP_EOL.PHP_EOL.$status.PHP_EOL;
	}
};
