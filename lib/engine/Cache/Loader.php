<?php

/**
 * Загрузчик данных из кэша.
 * @author Mic, 2010
 */
class Cache_Loader
{
	/**
	 * Ключ для кэша.
	 * @var string
	 */
	private $_key;
	
	/**
	 * Время жизни кэша в секундах.
	 * @var integer
	 */
	private $_lifetime;
	
	/**
	 * @param $key
	 * @param $lifetime
	 * @return Cache_Loader
	 */
	public static function Factory($key, $lifetime = null)
	{
		return new Cache_Loader($key, $lifetime);
	}
	
	public function __construct($key, $lifetime = null)
	{
		$this->_key = $key;
		$this->_lifetime = $lifetime;
	}
	
	public function Load()
	{
		return Env::Get()->cache->Load($this->_key, $this->_lifetime);
	}
	
	public function Save($data)
	{
		Env::Get()->cache->Save($this->_key, $data);
	}
};
