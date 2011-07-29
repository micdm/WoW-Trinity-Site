<?php

/**
 * Фасад для конфигураций.
 * @author Mic, 2010
 */
class Config
{
	/**
	 * Подправляет имя опции в зависимости от некоторых внешних факторов.
	 * @param string $class
	 * @param string $name
	 */
	private static function _ModifyName($class, $name)
	{
		if (method_exists($class, 'GetRewrites'))
		{
			//Если хранилище хочет применить какие-то изменения, выполняем:
			$rewrites = call_user_func(array($class, 'GetRewrites'));
			foreach ($rewrites as $prev => $new)
			{
				$name = preg_replace('#^'.$prev.'#', $new, $name);
			}
		}

		return $name;
	}
	
	/**
	 * Разбирает название опции и возвращает ее значение.
	 * @param string $class
	 * @param string $name
	 */
	private static function _ParseName($class, $name)
	{
		$parts = explode('/', $name);
		if (property_exists($class, $parts[0]) == false)
		{
			throw new Exception_Config_OptionNotFound();
		}
		
		//Запоминаем значение опции:
		$vars = get_class_vars($class);
		$current = $vars[$parts[0]];
		
		//Спускаемся вниз по массиву, если нужно:
		for ($i = 1; $i < count($parts); $i += 1)
		{
			if (isset($current[$parts[$i]]) == false)
			{
				throw new Exception_Config_OptionNotFound($name);
			}
			
			$current = $current[$parts[$i]];
		}
		
		return $current;
	}
	
	/**
	 * Возвращает значение опции из хранилища.
	 * Имя опции может быть в виде foo/bar для итерации по массиву.
	 * @param string $name
	 * @param string $config
	 */
	public function Get($name, $config = 'base')
	{
		switch ($config)
		{
			case 'base':
				$class = 'Config_Base';
				break;
				
			case 'game':
				$class = 'Config_Game';
				break;
				
			default:
				throw new Exception_Config_NotFound();
				break;
		}
		
		//Подправляем название опции, если необходимо:
		$name = self::_ModifyName($class, $name);

		//Находим опцию и возвращаем ее значение:
		return self::_ParseName($class, $name);
	}
};
