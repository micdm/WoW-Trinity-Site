<?php

/**
 * Назначает родительский шаблон.
 * @param array $params
 */
function smarty_function_dm_extend($params)
{
	//Указываем Smarty, что необходимо перейти к родительскому шаблону:
	Tpl_Smarty_Wrapper::$parent = $params['name'];
}
