<?php

/**
 * @package Third_Forum
 * @author Mic, 2010
 */
abstract class Third_Forum_Base
{
	/**
	 * Количество новостей по умолчанию.
	 * @var integer
	 */
	const NEWS_DEFAULT_COUNT						= 3;
	
	/**
	 * Количество сообщений с форума по умолчанию.
	 * @var integer
	 */
	const MESSAGES_DEFAULT_COUNT					= 7;
	
	/**
	 * @return Third_Forum_Base
	 */
	public static function Factory()
	{
		$className = Env::Get()->config->Get('forum/class');
		return new $className();
	}
	
	/**
	 * Нужно переопределить в дочернем классе.
	 * @return array
	 */
	protected function _LoadAnnouncesFromDb()
	{
		return array();
	}
	
	/**
	 * Нужно переопределить в дочернем классе.
	 * @param integer $count
	 * @return array
	 */
	protected function _LoadNewsFromDb($count)
	{
		return array();
	}
	
	/**
	 * Нужно переопределить в дочернем классе.
	 * @param array $count
	 * @return array
	 */
	protected function _LoadMessagesFromDb($count)
	{
		return array();
	}
	
	/**
	 * Возвращает случайный анонс.
	 * @return string
	 */
	public function GetRandomAnnounce()
	{
		$list = Env::Get()->cache->Load('main/announces', 3600);
		if (empty($list))
		{
			$list = $this->_LoadAnnouncesFromDb();
			Env::Get()->cache->Save(null, $list);
		}

		$key = $list ? array_rand($list) : null;
		return ($key === null) ? '' : $list[$key];
	}
	
	/**
	 * Загружает список новостей из темы на форуме.
	 * @param integer $count
	 * @return array
	 */
	public function LoadNews($count = null)
	{
		if ($count === null)
		{
			$count = self::NEWS_DEFAULT_COUNT;
		}
		
		//Смотрим в кэш:
		$list = Env::Get()->cache->Load('main/news/'.$count, 300);
		if (empty($list))
		{
			$list = $this->_LoadNewsFromDb($count);
			Env::Get()->cache->Save(null, $list);
		}
		
		return $list;
	}
	
	/**
	 * Загружает последние сообщения с форума.
	 * @param integer $count
	 * @return array
	 */
	public function LoadLastMessages($count = null)
	{
		if ($count === null)
		{
			$count = self::MESSAGES_DEFAULT_COUNT;
		}
		
		//Смотрим в кэш:
		$list = Env::Get()->cache->Load('main/welcome/forum/'.$count, 300);
		if (empty($list))
		{
			$list = $this->_LoadMessagesFromDb($count);
			Env::Get()->cache->Save(null, $list);
		}
		
		return $list;
	}
	
	/**
	 * Возвращает адрес, на котором находится форум.
	 * @return string
	 */
	public function GetUrl()
	{
		return Env::Get()->config->Get('forum/address');
	}
	
	/**
	 * Возвращает ссылку на профиль пользователя по идентификатору.
	 * @param integer $userId
	 * @return string
	 */
	public abstract function GetLinkToProfile($userId);
	
	/**
	 * Возвращает ссылку на тему по идентификатору.
	 * @param integer $topicId
	 * @return string
	 */
	public abstract function GetLinkToTopic($topicId);

	/**
	 * Возвращает ссылку на сообщение по идентификатору.
	 * @param integer $postId
	 * @return string
	 */
	public abstract function GetLinkToPost($postId);
};
