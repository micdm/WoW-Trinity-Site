<?php

/**
 * Форматирует дату в человекочитабельный вид.
 * @param mixed $time
 * @return string
 */
function smarty_modifier_dm_date_format($time)
{
	//Если на вход пришла строка, пытаемся ее преобразовать в метку времени:
	if (is_numeric($time) == false)
	{
		$time = strtotime($time);
	}
	
	$midnight = strtotime('00:00 today');
	
	$time = intval($time);
	$delta = time() - $time;
	if ($delta == 0)
	{
		$result = 'только что';
	}
	else if ($delta > 0 && $delta < 60)
	{
		//Меньше минуты назад:
		$result = Util_String::GetNumber($delta, array('секунда', 'секунды', 'секунд')).' назад';
	}
	else if ($delta > 0 && $delta < 60 * 59)
	{
		//Меньше часа назад:
		$result = Util_String::GetNumber(ceil($delta / 60), array('минута', 'минуты', 'минут')).' назад';
	}
	else
	{
		if ($delta > 0 && $time >= $midnight)
		{
			$day = 'сегодня';
		}
		else if ($time < $midnight && $time >= $midnight - 60 * 60 * 24)
		{
			$day = 'вчера';
		}
		else
		{
			//Список месяцев в родительном падеже:
			static $months = array('января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря');
			
			//Номер месяца:
			$month = date('m', $time) - 1;
			
			//День-месяц:
			$day = date('j', $time).' '.$months[$month];
			
			//Добавляем год, если нужно:
			$year = date('Y', $time);
			if ($year != date('Y'))
			{
				$day .= ' '.$year;
			}
		}

		$result = $day.', '.date('H:i', $time);
	}

    return $result;
}
