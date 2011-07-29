<?php

/**
 * Выводит статсное сообщение(я).
 * @param array $params
 * @return string
 */
function smarty_function_dm_status_msg($params)
{
	$chunks = array();
	foreach (Tpl_Smarty_Wrapper::GetStatusMsg($params['name']) as $msg)
	{
		$chunks[] = '<p class="status">'.Util_String::FormatSentence($msg).'</p>';
	}
	
	return implode('', $chunks);
}
