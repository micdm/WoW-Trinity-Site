<?php

/**
 * Анализатор-обработчик шаблонов.
 * @author Mic, 2010
 */
class Tpl_Parser
{
	/**
	 * Массив разобранных блоков.
	 * @var array
	 */
	private static $_blocks = array();
	
	/**
	 * Добавляет новый блок.
	 * @param string $name
	 * @param string $content
	 * @return string
	 */
	public static function AddBlock($name, $content)
	{
		$block = Tpl_Block::Factory($content);
		self::$_blocks[$name] = $block;

		return $block->content;
	}
	
	/**
	 * Возвращает содержимое блока.
	 * @param string $name
	 * @return string
	 */
	public static function GetBlock($name)
	{
		return isset(self::$_blocks[$name]) ? self::$_blocks[$name]->content : null;
	}
	
	/**
	 * Очищает список сохраненных блоков.
	 */
	public static function ClearBlocks()
	{
		self::$_blocks = array();
	}
};
