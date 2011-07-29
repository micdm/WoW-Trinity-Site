<?php

/**
 * Управление предметами в donate-системе.
 * @author Mic, 2010
 * @package User_Donating
 */
class User_Donating_Items
{
	/**
	 * Список предметов, нуждающихся в обновлении.
	 * @var array
	 */
	private static $_items;
	
	/**
	 * Обрабатывает ввод.
	 */
	private static function _ParseInput()
	{
		//Проверяем, что все нужное указано:
		if (Env::Get()->request->Post('items') == null)
		{
			throw new Exception_UserInput('укажите предметы');
		}

		//Пытаемся раскодировать JSON:
		$items = null;
		try
		{
			$items = json_decode(Env::Get()->request->Post('items'), true);
		}
		catch (Exception $e)
		{
			throw new Exception_UserInput('плохой JSON');
		}
		
		//Ждем массив:
		if (is_array($items) == false)
		{
			throw new Exception_UserInput('плохие данные');
		}
		
		self::$_items = $items;
	}
	
	/**
	 * Проверяет, корректно ли указаны идентификаторы предметов.
	 */
	private static function _CheckItems()
	{
		foreach (self::$_items as $entry => $item)
		{
			$count = Env::Get()->db->Get('game')->Query('
				SELECT COUNT(entry)
				FROM #world.item_template
				WHERE entry = :entry
			', array(
				'entry' => array('d', $entry)
			))->FetchOne();
			
			if (empty($count))
			{
				throw new Exception_Runtime('предмет не найден');
			}
		}
	}
	
	/**
	 * Обновляет информацию о предметах.
	 */
	private static function _Update()
	{
		//Перебираем все предметы по очереди:
		foreach (self::$_items as $entry => $item)
		{
			//Нужно удалить или обновить?
			if ($item == 0)
			{
				Env::Get()->db->Get('game')->Query('
					DELETE FROM #site.site_donate_goods
					WHERE entry = :entry
				', array(
					'entry' => array('d', $entry)
				));
			}
			else
			{
				Env::Get()->db->Get('game')->Query('
					REPLACE INTO #site.site_donate_goods (entry, price, is_active)
					VALUES (:entry, :price, :visible)
				', array(
					'entry' => array('d', $entry),
					'price' => array('d', abs($item['p'])),
					'visible' => array('d', $item['v'])
				));
			}
		}
	}
	
	/**
	 * Очищает кэш после любых изменений.
	 */
	private static function _ClearCache()
	{
		Env::Get()->cache->Clear(User_Operation_Donating::GetCacheKey(true));
	}
	
	public static function Run()
	{
		self::_ParseInput();
		self::_CheckItems();
		self::_Update();
		self::_ClearCache();
	}
};
