<?php

/**
 * Работа с секциями а-ля профилирование.
 * @author Mic, 2010
 */
class Dev_Debug_Section
{
	/**
	 * Список секций.
	 * @var array
	 */
	private static $_sections;
	
	/**
	 * Стек вызовов секций.
	 * @var array
	 */
	private static $_stack;
	
	/**
	 * Стартует секцию для замера производительности.
	 * @param string $name
	 * @param string $comment
	 */
	public static function Begin($name, $comment = '')
	{
		if (Env::Get()->debug->IsActive())
		{
			//Добавляем секцию:
			$section = array(
				'comment' => $comment,
				'start' => microtime(true),
				'memory' => memory_get_usage(),
			);

			if (self::$_stack)
			{
				//Если в стеке есть секция, добавляем ей вложенную:
				$last = &self::$_stack[count(self::$_stack) - 1];
				$last['children'][$name] = &$section;
			}
			else
			{
				//Добавляем секкцию на верхний уровень:
				self::$_sections[$name] = &$section;
			}
			
			//Кладем в стек, чтоб дальше иметь возможность закрыть секцию:
			self::$_stack[] = &$section;
		}
	}
	
	/**
	 * Завершает секцию.
	 * @param string $name
	 */
	public static function End()
	{
		if (Env::Get()->debug->IsActive())
		{
			//Выталкиваем из стека (приходится так извращаться, чтобы передать массив по ссылке):
			$section = &self::$_stack[count(self::$_stack) - 1];
			
			$section['finish'] = microtime(true);
			$section['total'] = number_format($section['finish'] - $section['start'], 6);
			$section['memory'] = memory_get_usage() - $section['memory'];
			
			array_pop(self::$_stack);
		}
	}
	
	/**
	 * Распечатывает секцию вместе с детьми.
	 * @param string $name
	 * @param array $section
	 * @param integer $level
	 * @param array $parent
	 * @return array
	 */
	private static function _PrintSection($name, $section, $level, $parent = null)
	{
		$chunks = array();
		
		//Выводим информацию о секции:
		$info = str_pad(str_repeat(' ', $level * 4).$name, 60).' = '.$section['total'].'/'.number_format($section['memory'] / 1024 / 1024, 3).' Мб ('.$section['comment'].')';
		if ($parent)
		{
			//Проценты от времени родителя:
			$info .= ' '.number_format(100 * $section['total'] / $parent['total'], 2).'%';
		}
		
		$chunks[] = $info;
		
		//Обходим детей, если они есть:
		if (isset($section['children']))
		{
			foreach ($section['children'] as $name => $child)
			{
				$chunks = array_merge($chunks, self::_PrintSection($name, $child, $level + 1, $section));
			}
		}
		
		return $chunks;
	}
	
	/**
	 * Возвращает отладочную информцию об использовании секций.
	 * @return string
	 */
	public static function GetDebugInfo()
	{
		$chunks = array();
		
		//Если какие-то секции не успели закончиться:
		if (sizeof(self::$_stack))
		{
			while (sizeof(self::$_stack))
			{
				self::End();
			}
		}
		
		//Обходим все секции верхнего уровня:
		if (self::$_sections)
		{
			foreach (self::$_sections as $name => $section)
			{
				$chunks = array_merge($chunks, self::_PrintSection($name, $section, 0));
			}
		}
		
		return 'Производительность:'.PHP_EOL.implode(PHP_EOL, $chunks);
	}
};
