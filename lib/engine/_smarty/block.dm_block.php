<?php

/**
 * Подставляет самый последний определенный блок либо создает новый.
 * @param array $params
 * @param string $content
 * @param object $smarty
 * @param bool $repeat
 * @return $string
 */
function smarty_block_dm_block($params, $content, &$smarty, $repeat)
{
	$result = '';
	if ($repeat == false)
	{
		//Пытаемся получить блок:
		$block = Tpl_Parser::GetBlock($params['name']);
		
		//Блока нет, добавляем первый:
		if (empty($block))
		{
			$block = Tpl_Parser::AddBlock($params['name'], $content);
		}
		
		$result = $block;
	}

	return $result;
}
