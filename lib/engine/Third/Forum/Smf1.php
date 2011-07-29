<?php

/**
 * Взаимодействие с форумом smf1.
 * @package Third_Forum
 * @author Mic, 2010
 * 
 * @deprecated Все используем phpbb3 :)
 */
class Third_Forum_Smf1 extends Third_Forum_Base
{
	protected function _LoadNewsFromDb($count)
	{
		$list = Env::Get()->db->Get('site')->Query('
			SELECT
				ID_TOPIC AS topic,
				ID_MSG AS msg,
				posterTime AS time,
				SUBSTRING_INDEX(subject, \' \', 4) AS subject,
				LENGTH(SUBSTRING_INDEX(subject, \' \', 4)) < LENGTH(subject) AS isLong,
				posterName AS poster,
				body
			FROM smf_messages
			WHERE ID_TOPIC = 239
			ORDER BY
				posterTime DESC
			LIMIT :limit
		', array(
			'limit' => array('d', $count)
		))->FetchAll();
		
		foreach ($list as $i => $item)
		{
			$list[$i]->body = preg_replace(array('#\[url=(.+)\](.+)\[/url\]#isU', '#\[b\](.+)\[/b\]#isU'), array('<a href="$1">$2</a>', '<b>$1</b>'), $item->body);
		}

		return $list;
	}
	
	protected function _LoadMessagesFromDb($count)
	{
		$list = Env::Get()->db->Get('site')->Query('
			SELECT
				t1.posterName,
				t1.posterTime,
				t1.ID_MEMBER AS posterId,
				t1.ID_MSG AS msgId,
				t2.name AS boardName,
				t3.ID_TOPIC AS topicId,
				SUBSTRING_INDEX(t4.subject, \' \', 6) AS topic,
				LENGTH(SUBSTRING_INDEX(t4.subject, \' \', 6)) < LENGTH(t4.subject) AS isLong,
				t1.body
			FROM smf_messages AS t1
				INNER JOIN smf_boards AS t2 USING(ID_BOARD)
				INNER JOIN smf_topics AS t3 ON(t3.ID_TOPIC = t1.ID_TOPIC)
				INNER JOIN smf_messages	AS t4 ON(t4.ID_MSG = t3.ID_FIRST_MSG)
			WHERE TRUE
				AND t2.passwd = \'\'
				AND t2.ID_BOARD NOT IN(41, 43)
			ORDER BY
				t1.posterTime DESC
			LIMIT :limit
		', array(
			'limit' => array('d', $count)
		))->FetchAll();

		return $list;
	}
};
