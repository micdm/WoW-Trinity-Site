<?php

/**
 * Обертка над Smarty.
 * @package Tpl_Smarty
 * @author Mic, 2010
 */
class Tpl_Smarty_Wrapper
{
	/**
	 * Объект шаблонизатора.
	 * @var Smarty
	 */
	private static $_smarty;
	
	/**
	 * Сообщения об ошибках для пользователя.
	 * @var array
	 */
	private static $_errors;
	
	/**
	 * Массив-стек контекстов.
	 * @var array
	 */
	private static $_contexts = array();

	/**
	 * Родительский шаблон (от которого наследуется текущий).
	 * @var string
	 */
	public static $parent;
	
	/**
	 * Инициализирует Smarty-объект.
	 */
	private static function _Init()
	{
		if (empty(self::$_smarty))
		{
			$smarty = new Smarty();
			$smarty->error_reporting = error_reporting();
			$smarty->compile_dir = SITE_ROOT.'tmp/';
			$smarty->plugins_dir[] = ENGINE_ROOT.'_smarty/';
			//$smarty->load_filter("pre" , "dm_insert");
			//$smarty->load_filter("post" , "dm_remove_spaces");
			
			$smarty->force_compile = true;
			
			//$smarty->cache_dir = CACHE_ROOT.'smarty/';
			//$smarty->caching = true;
			
			self::$_smarty = $smarty;
		}
	}
	
	/**
	 * Делает контекст текущим.
	 * @param Tpl_Context $context
	 */
	private static function _AssignContext(Tpl_Context $context)
	{
		//Очищаем переменные и загружаем контекст:
		self::$_smarty->clear_all_assign();
		foreach ($context->GetAll() as $key => $value)
		{
			self::$_smarty->assign($key, $value);
		}
	}
	
	/**
	 * Назначает переменные из контекста.
	 * @param Tpl_Context $new
	 */
	public static function Assign(Tpl_Context $new)
	{
		self::_Init();
		
		//Кладем в стек текущий контекст:
		$prev = Tpl_Context::Factory();
		foreach (self::$_smarty->get_template_vars() as $key => $value)
		{
			$prev->Set($key, $value);
		}
		
		//Я сказал, кладем:
		self::$_contexts[] = $prev;
		
		//Загружаем новый контекст:
		self::_AssignContext($new);
	}
	
	public static function Restore()
	{
		//Восстанавливаем последний сохраненный контекст:
		if (count(self::$_contexts))
		{
			self::_AssignContext(array_pop(self::$_contexts));
		}
	}
	
	/**
	 * Обрабатывает шаблон средствами Smarty.
	 * @param string $template
	 * @return string
	 */
	public static function Fetch($template)
	{
		self::_Init();
		
		self::$parent = null;
		
		//Обрабатываем:
		$result = self::$_smarty->fetch(TEMPLATES_ROOT.$template);
		
		if (self::$parent)
		{
			$result = self::Fetch(self::$parent);
		}
		
		//Удаляем блоки на выходе:
		Tpl_Parser::ClearBlocks();

		return $result;
	}
	
	/**
	 * Добавляет сообщение об ошибке для пользователя.
	 * @param string $name
	 * @param string $msg
	 */
	public static function _AddErrorMsg($name, $msg)
	{
		self::$_errors[$name][] = $msg;
	}
	
	/**
	 * Возвращает по имени сообщения об ошибках.
	 * @param string $name
	 * @return array
	 */
	public static function GetErrorMsg($name)
	{
		return empty(self::$_errors[$name]) ? array() : self::$_errors[$name];
	}

	/**
	 * Добавляет статусное сообщение для пользователя.
	 * @param string $name
	 * @param string $msg
	 */
	public static function _AddStatusMsg($name, $msg)
	{
		Tpl_Smarty_StatusMsg::Add($name, $msg);
	}
	
	/**
	 * Возвращает по имени статусные сообщения.
	 * @param string $name
	 * @return array
	 */
	public static function GetStatusMsg($name)
	{
		return Tpl_Smarty_StatusMsg::Get($name);
	}
};
