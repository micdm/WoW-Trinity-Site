<?php

/**
 * Изменение оформления сайта в зависимости от даты.
 * @author Mic, 2010
 */
class Site_Event
{
	/**
	 * Текущее событие.
	 * @var array
	 */
	private static $_event;
	
	/**
	 * Вычисляет текущее событие.
	 * @return array
	 */
	private static function _GetCurrentEvent()
	{
		$now = time();
		foreach (Env::Get()->config->Get('events') as $event)
		{
			if (strtotime($event['start']) <= $now && strtotime($event['finish']) >= $now)
			{
				return $event;
			}
		}
		
		return null;
	}
	
	/**
	 * Возвращает текущее событие.
	 * @return array
	 */
	public static function GetEventCss()
	{
		//Вычисляем:
		if (self::$_event === null)
		{
			self::$_event = self::_GetCurrentEvent();
		}
		
		$event = array();
		foreach (array('css') as $field)
		{
			$event[$field] = empty(self::$_event[$field]) ? null : self::$_event[$field];
		}
		
		//Анонс-совет:
		if (isset(self::$_event['tip']))
		{
			$tip = array();
			foreach (array('link', 'text') as $field)
			{
				$tip[$field] = self::$_event['tip'][$field];
			}

			$event['tip'] = $tip;
		}

		return $event;
	}
};
