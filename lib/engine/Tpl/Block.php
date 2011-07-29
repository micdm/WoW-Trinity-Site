<?php

/**
 * Отдельный блок шаблона.
 * @author Mic, 2010
 */
class Tpl_Block
{
	/**
	 * Содержимое блока.
	 * @var string
	 */
	public $content;
	
	public static function Factory($content)
	{
		return new Tpl_Block($content);
	}

	public function __construct($content)
	{
		$this->content = $content;
	}
};
