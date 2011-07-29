<?php

/**
 * Различные частоиспользуемые функции.
 * @author Mic, 2010
 */
class Site_Util
{
	/**
	 * Вычисляет время жизни сервера без вайпов.
	 * @return array
	 */
	public static function CalculateServerLifetime()
	{
		$borned = getdate(time() - Env::Get()->config->Get('bornTime'));
		$noWipes = array();
		$noWipes['years'] = $borned['year'] - 1970;

		//Подправляем надпись "N лет и 12 месяцев":
		if ($borned['mon'] == 12)
		{
			$noWipes['years'] += 1;
			$noWipes['months'] = 0;
		}
		else
		{
			$noWipes['months'] = $borned['mon'];
		}
		
		return $noWipes;
	}
	
	/**
	 * Возвращает статистику сервера на mmotop.ru.
	 * @return array
	 */
	public static function GetMmotopStats()
	{
		//Ищем в кэше:
		$stats = Env::Get()->cache->Load('main/mmotop', 3600);
		if ($stats === null)
		{
			//Пытаемся загрузить:
			try
			{
				$stats = Third_Mmotop::GetServerStats();
			}
			catch (Exception_Third_Mmotop_Base $e)
			{
				$stats = array();
			}
			
			Env::Get()->cache->Save(null, $stats);
		}
		
		return $stats;
	}
};
