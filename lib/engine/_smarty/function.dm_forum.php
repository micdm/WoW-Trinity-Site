<?php

/**
 * Возвращает адрес страницы на форуме.
 * @param array $params
 * @return string
 */
function smarty_function_dm_forum($params)
{
	$forum = Third_Forum_Base::Factory();
	
	if (isset($params['target']))
	{
		//Идентификатор цели обязателен:
		if (empty($params['id']))
		{
			throw new Exception_Tpl_Smarty_BadPluginParameter();
		}
		
		$target = $params['target'];
		$id = $params['id'];
		if ($target == 'topic')
		{
			//Ссылка на тему:
			$result = $forum->GetLinkToTopic($id);
		}
		else if ($target == 'post')
		{
			//Ссылка на сообщение:
			$result = $forum->GetLinkToPost($id);
		}
		else if ($target == 'profile')
		{
			//Ссылка на профиль:
			$result = $forum->GetLinkToProfile($id);
		}
		else
		{
			throw new Exception_Tpl_Smarty_BadPluginParameter('unknown target "'.$target.'"');
		}
	}
	else
	{
		//Просто ссылка на форум:
		$result = $forum->GetUrl();
	}
	
	return htmlspecialchars($result);
}
