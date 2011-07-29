<?php

/**
 * Пускатель юнит-тестов.
 * @author Mic, 2010
 */
class Dev_TestRunner
{
	/**
	 * Возвращает список доступных тестов в формате [уровень, путь].
	 * @return array
	 */
	public static function LoadTestList()
	{
		$dirs = array(array(0, UNIT_TESTS_ROOT));

		$result = array();
		while ($dirs)
		{
			//Текущая просматриваемая директория:
			list($level, $current) = array_pop($dirs);

			$dir = dir($current);
			while ($file = $dir->read())
			{
				if ($file != '.' && $file != '..')
				{
					$path = Util_String::CombineSlashes($current.'/'.$file);
					if (is_dir($path))
					{
						//Директории добавляем в стек для последующего просмотра:
						$dirs[] = array($level + 1, $path);
					}
					else if (preg_match('#Test\.php$#', $path))
					{
						//PHP-скрипты складываем в результирующий список (вспомогательные классы-помощники пропускаем):
						$result[] = array($level, $path, str_replace(array(UNIT_TESTS_ROOT, '.php', '/'), array('', '', '_'), $path));
					}
				}
			}
		}
		
		//Сортируем по алфавиту:
		usort($result, create_function('$a, $b', 'return strcmp($a[2], $b[2]);'));

		return $result;
	}
	
	public static function Run($list)
	{
		ob_start();
		foreach ($list as $test)
		{
			//Выполняем через внешнюю команду:
			$command = 'cd '.UNIT_TESTS_ROOT.' && phpunit --verbose --loader TestLoader '.$test;
			print($command.PHP_EOL);
			print(htmlspecialchars(`$command`));
			print('<span style="background: lightgray">'.str_repeat('-', 100).'</span>'.PHP_EOL);
		}
		
		$output = ob_get_clean();
		
		//Подсвечиваем все ошибки/сообщения:
		$output = preg_replace(
			array(
				'#(There (?:was|were) \d+ (?:error|errors|failure|failures):)#',
				'#(There (?:was|were) \d+ (?:incomplete) (?:test|tests):)#'
			),
			array(
				'<span style="background: red">$1</span>',
				'<span style="background: gold">$1</span>'
			), $output);

		return $output;
	}
};
