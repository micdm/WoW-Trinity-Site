<?php

/**
 * Базовый класс для всех исключений.
 * @package Exception
 * @author Mic, 2010
 */
class Exception_Runtime extends RuntimeException
{
	public function __toString()
	{
		$result = array();
		
		$result[] = 'URI: '.Env::Get()->request->GetUri();
		$result[] = 'ERR: '.get_class($this);
		$result[] = 'MSG: '.$this->getMessage();

		//Переформатируем стандартное сообщение:
		$stack = $this->getTraceAsString();

		//Заменяем куски абсолютного пути для краткости:
		$stack = str_replace(SITE_ROOT, '/', $stack);
		
		//Еще немного форматируем:
		$stack = str_replace('): ', "):\n".str_repeat(' ', 4), $stack);
		
		$result[] = "STACK:\n".$stack;
		return implode("\n", $result)."\n";
	}
};
