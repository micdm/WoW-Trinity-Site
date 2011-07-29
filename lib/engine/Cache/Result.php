<?php

/**
 * Элемент кэша, сформированный для употребления.
 * @author Mic, 2010
 */
class Cache_Result
{
	/**
	 * Интервал в секундах, после которого кэш испортится.
	 * @var integer
	 */
	public $expire;
	
	/**
	 * Непосредственно данные кэша.
	 * @var mixed
	 */
	public $data;
	
	public static function Factory($expire, $data)
	{
		return new Cache_Result($expire, $data);
	}
	
	public function __construct($expire, $data)
	{
		$this->expire = $expire;
		$this->data = $data;
	}
};
