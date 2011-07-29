<?php

/**
 * Координатор загрузчиков.
 * @author Mic, 2010
 */
class Loader_Multi
{
	/**
	 * Массив загрузчиков.
	 * @var array
	 */
	private $_loaders = array();
	
	/**
	 * @return Loader_Multi
	 */
	public static function Factory()
	{
		return new Loader_Multi();
	}
	
	/**
	 * Добавляет загрузчик в очередь.
	 * @param Loader_Interface $loader
	 */
	public function AddLoader($loader)
	{
		$this->_loaders[] = $loader;
	}
	
	/**
	 * Запускает загрузчики один за другим.
	 * @return mixed
	 */
	public function Run()
	{
		$result = null;
		
		//Загружаем данные:
		foreach ($this->_loaders as $loader)
		{
			if ($result = $loader->Load())
			{
				break;
			}
		}
		
		//Сохраняем данные:
		if ($result)
		{
			foreach ($this->_loaders as $loader)
			{
				$loader->Save($result);
			}
		}
		
		return $result;
	}
};
