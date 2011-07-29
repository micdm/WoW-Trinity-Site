<?php

/**
 * Поддержка сервера рублем.
 * @package User_Operation
 * @author Mic, 2010
 */
class User_Operation_Donating extends User_Operation_Base
{
	/**
	 * Список отображаемых категорий.
	 * @var array
	 */
	protected static $_categories;
	
	protected function _Setup()
	{
		$this
			->_AddAction('main', 'Donating_Main')
			->_AddAction('gold', 'Donating_Gold')
			->_AddAction('edit_items', 'Donating_ItemList')
			->_AddAction('transfer', 'Donating_Transfer');
	}
	
	/**
	 * Возвращает ключ для кэша.
	 * @param boolean $extended
	 * @return string
	 */
	protected static function _GetCacheKey($category = null, $extended = false)
	{
		return $category ? 'donate/items/'.$category.'/'.intval($extended) : 'donate/items/all';
	}
	
	/**
	 * Возвращает номер категории для отображения.
	 * @param integer $category
	 * @return integer
	 */
	public static function GetCategory($category)
	{
		$categories = self::GetCategories();
		if ($category)
		{
			if (empty($categories[$category]))
			{
				throw new Exception_Http_NotFound();
			}
			
			return $category;
		}
		else if (count($categories))
		{
			$first = reset($categories);
			return $first['id'];
		}
	}
	
	/**
	 * Возвращает список категорий для навигации.
	 * @return array
	 */
	public static function GetCategories()
	{
		if (self::$_categories !== null)
		{
			return self::$_categories;
		}
		
		self::$_categories = Env::Get()->cache->Load('donate/categories', 3600);
		if (self::$_categories !== null)
		{
			return self::$_categories;
		}
		
		$categories = Env::Get()->db->Get('game')->Query('
			SELECT COUNT(*) AS count, it.class
			FROM #site.site_donate_goods AS sdg
				INNER JOIN #world.item_template AS it ON(it.entry = sdg.entry)
			WHERE sdg.is_active = 1
			GROUP BY it.class
		')->FetchAll();
		
		$categories = array_filter($categories, create_function('$category', 'return $category["count"] != 0;'));
		
		self::$_categories = array();
		$names = Env::Get()->config->Get('categories', 'game');
		foreach ($categories as $category)
		{
			$id = $category['class'];
			self::$_categories[$id] = array(
				'id' => $id,
				'name' => $names[$id],
			);
		}
		
		Env::Get()->cache->Save(null, self::$_categories);
		return self::$_categories;
	}
	
	/**
	 * Возвращает вещи, доступные для продажи.
	 * @param boolean $extended
	 * @return array
	 */
	public static function GetItems($category = 0, $extended = false)
	{
		$category = self::GetCategory($category);

		$result = Env::Get()->cache->Load(self::_GetCacheKey($category, $extended), 3600);
		if (empty($result))
		{
			//Загружаем список вещей:
			if ($extended)
			{
				$ids = Env::Get()->db->Get('game')->Query('
					SELECT sdg.entry
					FROM #site.site_donate_goods AS sdg
						INNER JOIN #world.item_template AS it ON(it.entry = sdg.entry)
					WHERE it.class = :category
					ORDER BY
						it.subclass,
						sdg.price
				', array(
					'category' => array('d', $category)
				))->FetchColumn();
			}
			else
			{
				$ids = Env::Get()->db->Get('game')->Query('
					SELECT sdg.entry
					FROM #site.site_donate_goods AS sdg
						INNER JOIN #world.item_template AS it ON(it.entry = sdg.entry)
					WHERE TRUE
						AND it.class = :category
						AND sdg.is_active = TRUE
				', array(
					'category' => array('d', $category)
				))->FetchColumn();
			}

			$items = World_Item::Factory($ids);
			if ($items)
			{
				foreach ($items as $item)
				{
					$result[$item->GetSubclass()][] = $item;
				}
			}

			Env::Get()->cache->Save(null, $result);
		}
		
		return $result;
	}
	
	/**
	 * Очищает весь кэш.
	 */
	public static function ClearCache()
	{
		Env::Get()->cache->Clear('donate');
	}
};
