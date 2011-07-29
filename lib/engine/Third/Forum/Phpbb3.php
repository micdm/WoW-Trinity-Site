<?php

/**
 * Взаимодействие с форумом phpbb3.
 * @package Third_Forum
 * @author Mic, 2010
 */
class Third_Forum_Phpbb3 extends Third_Forum_Base
{
	/**
	 * Преобразовыает в строке bb-коды в теги и возвращает результат.
	 * @param string $text
	 * @return string
	 */
	private function _ParseBbCodes($text)
	{
		$replace = array(
			'#\[url=(.+):[\d\w]+\](.+)\[/url:[\d\w]+\]#isU' => '<a href="$1">$2</a>',
			'#\[b:[\d\w]+\](.+)\[/b:[\d\w]+\]#isU' => '<b>$1</b>',
			'#\[quote=&quot;.*?&quot;:[\d\w]+\].+?\[/quote:[\d\w]+\]#isU' => ''
		);
		
		return preg_replace(array_keys($replace), array_values($replace), $text);
	}
	
	protected function _LoadAnnouncesFromDb()
	{
		$topic = Env::Get()->config->Get('forum/announcesTopicId');
		if (empty($topic))
		{
			return array();
		}
		
		$list = Env::Get()->db->Get('site')->Query('
			SELECT post_text
			FROM phpbb_posts
			WHERE 1 = 1
				AND topic_id = :topic
				AND icon_id = 0
		', array(
			'topic' => array('d', $topic),
		))->FetchColumn();
		
		return array_map(array($this, '_ParseBbCodes'), $list);
	}
	
	protected function _LoadNewsFromDb($count)
	{
		$list = Env::Get()->db->Get('site')->Query('
			SELECT
				p.post_id AS postId,
				p.topic_id AS topic,
				p.post_time AS time,
				p.post_subject AS subject,
				COALESCE(u.username, p.post_username) AS poster,
				p.post_text AS body
			FROM phpbb_posts AS p
				LEFT JOIN phpbb_users AS u ON(u.user_id = p.poster_id)
			WHERE p.topic_id = :topic
			ORDER BY p.post_id DESC
			LIMIT :limit
		', array(
			'topic' => array('d', Env::Get()->config->Get('forum/newsTopicId')),
			'limit' => array('d', $count)
		))->FetchAll();
		
		foreach ($list as &$item)
		{
			//Убираем теги (всякие смайлики):
			$item['body'] = strip_tags($item['body']);
			
			//Обрабатываем bb-коды в теле:
			$item['body'] = $this->_ParseBbCodes($item['body']);
			
			//Разбиваем по переносам строк:
			$lines = explode(PHP_EOL, $item['body']);
			
			$item['body'] = array();
			$item['hasCut'] = false;
			foreach ($lines as $i => $line)
			{
				//Если строка пустая, а за ней идет еще одна, считаем это местом разрыва новости:
				if (trim($line) == '' && isset($lines[$i + 1]))
				{
					$item['hasCut'] = true;
					break;
				}
				
				$item['body'][] = $line;
			}
		}
		
		return $list;
	}
	
	/**
	 * Возвращает очередной подфорум, который надо исключить из отображения.
	 * @param array $forums
	 * @param array $exclude
	 * @return integer
	 */
	protected function _GetExcludedSubforum($forums, $exclude)
	{
		//Исключаем форум, если у него есть пароль, либо какой-то из его родителей уже скрыт.
		foreach ($forums as $forum)
		{
			if ($forum['password'])
			{
				return $forum['id'];
			}
			
			foreach ($forum['parents'] as $id => $parent)
			{
				if (in_array($id, $exclude))
				{
					return $forum['id'];
				}
			}
		}
		
		return 0;
	}
	
	/**
	 * Возвращает список идентификаторов форумов, сообщения из которых не нужно показывать на главной.
	 * @return array
	 */
	protected function _GetExcludedForums()
	{
		//Выбираем идентификаторы форумов, которые нужно проигнорировать:
		$config = Env::Get()->config;
		$exclude = $config->Get('forum/excludeForums');
		
		//Если темы про обмен нужно игнорировать, добавляем в список исключений:
		if ($config->Get('forum/excludeExchangeMessages'))
		{
			$exclude[] = $config->Get('forum/exchangeForumId');
		}
		
		$forums = Env::Get()->db->Get('site')->Query('
			SELECT forum_id AS id, forum_parents AS parents, forum_password AS password
			FROM phpbb_forums
			WHERE forum_parents != \'\'
		')->FetchAll();

		array_walk($forums, create_function('&$forum', '$forum["parents"] = unserialize($forum["parents"]);'));
		while ($id = $this->_GetExcludedSubforum($forums, $exclude))
		{
			$exclude[] = $id;
			
			//Удаляем найденный форум из списка:
			foreach ($forums as $i => $forum)
			{
				if ($forum['id'] == $id)
				{
					unset($forums[$i]);
					break;
				}
			}
		}

		return $exclude;
	}
	
	protected function _LoadMessagesFromDb($count)
	{
		$exclude = $this->_GetExcludedForums();
		
		$where = 'TRUE';
		if (count($exclude))
		{
			$where = 'p.forum_id NOT IN('.implode(',', $exclude).')';
		}
		
		return Env::Get()->db->Get('site')->Query('
			SELECT
				p.post_id AS postId,
				p.poster_id AS posterId, 
				p.post_time AS time,
				COALESCE(u.username, p.post_username) AS posterName,
				t.topic_title AS topicName,
				f.forum_name AS forumName
			FROM phpbb_posts AS p
				INNER JOIN phpbb_topics AS t ON(t.topic_id = p.topic_id)
				INNER JOIN phpbb_forums AS f ON(f.forum_id = p.forum_id)
				LEFT JOIN phpbb_users AS u ON(u.user_id = p.poster_id)
			WHERE '.$where.'
			ORDER BY p.post_id DESC
			LIMIT :limit
		', array(
			'limit' => array('d', $count)
		))->FetchAll();
	}
	
	public function GetLinkToProfile($userId)
	{
		return $this->GetUrl().'memberlist.php?mode=viewprofile&u='.$userId;
	}
	
	public function GetLinkToTopic($topicId)
	{
		return $this->GetUrl().'viewtopic.php?t='.$topicId;
	}
	
	public function GetLinkToPost($postId)
	{
		return $this->GetUrl().'viewtopic.php?p='.$postId.'#p'.$postId;
	}
};
