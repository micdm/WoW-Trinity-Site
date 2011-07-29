<?php

/**
 * Приводит URL к абсолютному виду (адресация от корня сайта).
 * @param array $params
 * @return string
 */
function smarty_function_dm_abs_path($params)
{
	//Если URL не указан, берем текущий:
	$url = empty($params['url']) ? Env::Get()->request->GetUrl() : $params['url'];
	
	$result = Env::Get()->request->GetAbsoluteUrl($url, isset($params['host']));
	return $result;
}
