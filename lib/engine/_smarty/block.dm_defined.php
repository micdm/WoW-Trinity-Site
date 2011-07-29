<?php

/**
 * Возвращает содержимое только в том случае, если константа определена.
 * @param array $params
 * @param string $content
 * @return string
 */
function smarty_block_dm_defined($params, $content)
{
	return defined($params["name"]) ? $content : "";
}
