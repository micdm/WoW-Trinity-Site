<?php

/**
 * Выводит сообщение(я) об ошибке.
 * @param array $params
 * @return string
 */
function smarty_function_dm_error_msg($params)
{
	$chunks = array();
	foreach (Tpl_Smarty_Wrapper::GetErrorMsg($params['name']) as $msg)
	{
		$chunks[] = '<p class="error">'.Util_String::FormatSentence($msg).'</p>';
	}
	
	return implode('', $chunks);
}
