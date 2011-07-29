<?php

/**
 * Подбирает слово в правильном числе.
 * @param array $params
 * @return string
 */
function smarty_function_dm_number($params)
{
	$args = array($params['value'], explode(',', $params['numbers']));
	if (isset($params['format']))
	{
		$args[] = $params['format'];
	}
	
	return call_user_func_array(array('Util_String', 'GetNumber'), $args);
}
