<?php

/**
 * Редактирование списка предметов для donate-системы.
 * @package User_Operation_Action_Donating
 * @author Mic, 2010
 */
class User_Operation_Action_Donating_ItemList extends User_Operation_Action_Base
{
	protected function _Setup()
	{
		$options = array(
			'canBeNull' => true,
			'datatype' => 'array',
		);
		
		$this
			->_AddPlainField('add')
			->_AddPlainField('entries', $options)
			->_AddPlainField('active', $options)
			->_AddPlainField('remove', $options)
			->_AddPlainField('prices', $options);
	}
	
	public function GetDescription($accounts, $characters, $plain, $custom)
	{
		return sprintf('Редактирование списка предметов в donate-системе');
	}
	
	protected function _CheckAdditionalConditions()
	{
		//Проверяем, что пользователь - администратор:
		if (Env::Get()->user->GetAccount()->IsAdministrator() == false)
		{
			throw new Exception_User_Operation_BadCondition('Вы не можете сделать это');
		}
	}
	
	protected function _DoSomeActions()
	{
		$this->_AddItems();
		$this->_UpdateItems();
		
		$this->_operation->ClearCache();
		$this->_SetSuccessMessages('список предметов обновлен');
	}
	
	/**
	 * Проверяет формат команды для добавления вещей.
	 */
	protected function _CheckAddCommandFormat()
	{
		if (preg_match('#^(\d+:\d+(\.\d+)?(\s+|$))+#', $this->GetPlainField('add')) == false)
		{
			throw new Exception_User_Operation_BadCondition('список новых предметов указан в некорректном формате');
		}
	}
	
	/**
	 * Проверяет, что все предметы существуют.
	 * @param array $parts
	 */
	protected function _CheckIfItemsNotFound($parts)
	{
		$entries = array_map('reset', $parts);
		$items = World_Item::Factory($entries);
		
		$notFound = null;
		foreach ($items as $i => $item)
		{
			if ($item == null)
			{
				$notFound[] = $entries[$i];
			}
		}

		if ($notFound)
		{
			throw new Exception_User_Operation_BadCondition('предметы '.implode(', ', $notFound).' из списка новых не существуют');
		}
	}
	
	/**
	 * Проверяет цены и по пути преобразовывает в float.
	 * @param array $parts
	 */
	protected function _CheckPrices(&$prices)
	{
		foreach ($prices as &$price)
		{
			$price = floatval($price);
			if ($price <= 0)
			{
				throw new Exception_User_Operation_BadCondition('некорректная цена '.$price);
			}
		}
	}
	
	/**
	 * Добавляет новые предметы.
	 */
	protected function _AddItems()
	{
		$value = $this->GetPlainField('add');
		if (empty($value))
		{
			return;
		}
		
		//Проверяем формат:
		$this->_CheckAddCommandFormat();
		
		//Разбиваем по пробелу:
		$parts = preg_split('#\s+#', $value);
		
		//Разбиваем по двоеточию:
		$parts = array_map(create_function('$part', 'return explode(":", $part);'), $parts);
		
		//Проверяем, что все предметы существуют:
		$this->_CheckIfItemsNotFound($parts);
		
		//Добавляем предметы:
		foreach ($parts as $part)
		{
			Env::Get()->db->Get('game')->Query('
				REPLACE INTO #site.site_donate_goods (entry, price, is_active)
				VALUES (:entry, :price, 1)
			', array(
				'entry' => array('d', $part[0]),
				'price' => array('d', $part[1]),
			));
		}
	}
	
	/**
	 * Обновляет список предметов.
	 */
	protected function _UpdateItems()
	{
		$active = $this->GetPlainField('active');
		$remove = $this->GetPlainField('remove');
		$prices = $this->GetPlainField('prices');
		
		//Проверяем, что цены правильные:
		$this->_CheckPrices($prices);
		
		//Перебираем все предметы по очереди:
		foreach ($this->GetPlainField('entries') as $i => $entry)
		{
			//Нужно удалить или обновить?
			if (in_array($entry, $remove))
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
					'price' => array('d', $prices[$i]),
					'visible' => array('d', in_array($entry, $active))
				));
			}
		}
	}
};
