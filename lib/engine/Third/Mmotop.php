<?php

/**
 * Функции для работы с сайтом mmotop.ru.
 * @package Third
 * @author Mic, 2010
 */
class Third_Mmotop
{
	/**
	 * Адрес сайта.
	 * @var string
	 */
	const SITE_URL									= 'http://wow.mmotop.ru/';
	
	/**
	 * Количество страниц, на которых будем искать сервер.
	 * @var integer
	 */
	const SEARCH_ON_PAGES							= 3;
	
	const FIELD_ID									= 0;
	const FIELD_DATE								= 1;
	const FIELD_NAME								= 3;
	const FIELD_TYPE								= 4;
	
	const TYPE_NORMAL								= 1;
	const TYPE_SMS									= 2;
	
	/**
	 * Название переменной, в которую пишется дата последнего награждения.
	 * @var string
	 */
	const LAST_REWARDED_VARIABLE					= 'mmotop_last_rewarded';
	
	/**
	 * Список персонажей, которых пока не наградили.
	 * @var array
	 */
	private static $_votes;
	
	/**
	 * Возвращает статистику (позицию и голоса) сервера в рейтинге.
	 * @return array
	 */
	public static function GetServerStats()
	{
		$id = Env::Get()->config->Get('mmotop/id');

		//По очереди загружаем страницы:
		for ($i = 1; $i <= self::SEARCH_ON_PAGES; $i += 1)
		{
			$path = self::_GetPathForDocument(self::SITE_URL.'page-'.$i.'/');
			$title = $path->evaluate('//a[@class="server_name"][@href="http://wow.mmotop.ru/server/'.$id.'/"]')->item(0);
			if ($title == null)
			{
				continue;
			}

			$serverBlock = $title->parentNode->parentNode;
			return array(
				'place' => self::_GetPlace($path, $serverBlock),
				'total' => self::_GetTotalCount($path),
				'votes' => self::_GetVotes($path, $serverBlock),
			);
		}
		
		return array();
	}
	
	/**
	 * Загружает документ и создает для него XPath.
	 * @param string $url
	 * @return DOMXPath
	 */
	protected static function _GetPathForDocument($url)
	{
		$document = new DOMDocument();
		
		try
		{
			$document->loadHTMLFile($url);
		}
		catch (Exception_Php_Warning $e)
		{
			
		}
			
		return new DOMXPath($document);
	}
	
	/**
	 * Возвращает общее количество серверов, зарегистрированных в MMOTOP.
	 * @param DOMXPath $path
	 * @return integer
	 */
	protected static function _GetTotalCount($path)
	{
		//Узнаем номер последней страницы:
		$list = $path->evaluate('//div[@class="pagination"]/a');
		$link = $list->item($list->length - 2)->attributes->getNamedItem('href')->textContent;
		
		//Находим последний сервер на странице:
		$path = self::_GetPathForDocument($link);
		$servers = $path->evaluate('//div[@class="server"]');
		$last = $servers->item($servers->length - 1);
		
		return self::_GetPlace($path, $last);
	}
	
	/**
	 * Выделяет из блока позицию сервера.
	 * @param DOMXPath $path
	 * @param DOMElement $block
	 * @throws Exception_Third_Mmotop_PlaceNotFound
	 * @return integer
	 */
	protected static function _GetPlace($path, $block)
	{
		$result = $path->evaluate('.//div[@class="server_position"]', $block)->item(0);
		if ($result == null)
		{
			throw new Exception_Third_Mmotop_PlaceNotFound();
		}
		
		return preg_replace('#[^\d]#', '', $result->textContent);
	}
	
	/**
	 * Выделяет из блока количество голосов.
	 * @param DOMXPath $path
	 * @param DOMElement $block
	 * @throws Exception_Third_Mmotop_VotesNotFound
	 * @return integer
	 */
	protected static function _GetVotes($path, $block)
	{
		$result = $path->evaluate('.//a[@class="server_votes"]', $block)->item(0);
		if ($result == null)
		{
			throw new Exception_Third_Mmotop_VotesNotFound();
		}
		
		return preg_replace('#[^\d]#', '', $result->textContent);
	}
	
	/**
	 * Возвращает дату последней записи.
	 * @return string
	 */
	private static function _GetLastRecordDate()
	{
		return Env::Get()->db->Get('game')->Query('
			SELECT COALESCE(MAX(added), FROM_UNIXTIME(0))
			FROM #site.site_mmotop_votes
		')->FetchOne();
	}
	
	/**
	 * Разбирает MMOTOP-файл и сохраняет информацию о проголосовавших.
	 */
	private static function _LoadNewData()
	{
		//Забираем информацию с mmotop.ru и конвертируем в правильную кодировку:
		$url = Env::Get()->config->Get('mmotop/votes');
		$source = Util_Charset::Convert(file_get_contents($url), Util_Charset::CHARSET_CP1251, Util_Charset::CHARSET_UTF8);
		
		//Разбиваем и удаляем пустые строки:
		$strings = array_filter(array_map('trim', explode("\n", $source)), 'strlen');
		
		//Узнаем номер последней записи, которая уже хранится в базе:
		$last = strtotime(self::_GetLastRecordDate());
		
		//Добавляем новые записи в базу:
		$db = Env::Get()->db->Get('game');
		foreach ($strings as $string)
		{
			//В качестве разделителя используется TAB:
			$data = explode("\t", $string);
			
			//Преобразовываем дату:
			$date = strtotime($data[self::FIELD_DATE]);
			
			//TODO неправильно проверять по дате, можно пропустить кого-нибудь.
			if ($date > $last)
			{
				$db->Query('
					INSERT IGNORE INTO #site.site_mmotop_votes (id, name, added, type)
					VALUES (:id, :name, :added, :type)
				', array(
					'id' => array('d', $data[self::FIELD_ID]),
					'name' => array('d', $data[self::FIELD_NAME]),
					'added' => array('s', date('Y-m-d H:i:s', $date)),
					'type' => array('d', $data[self::FIELD_TYPE]),
				));
			}
			else
			{
				break;
			}
		}
	}
	
	/**
	 * Заполняет идентификаторы персонажей по именам.
	 */
	private static function _SearchCharactersByName()
	{
		Env::Get()->db->Get('game')->Query('
			UPDATE #site.site_mmotop_votes AS smv, characters AS c
			SET smv.guid = c.guid
			WHERE TRUE
				AND smv.guid = 0
				AND smv.name = c.name
		');
	}
	
	/**
	 * Загружает список голосов, по которым можно начислить награду.
	 * @return array
	 */
	public static function LoadVotes()
	{
		if (self::$_votes === null)
		{
			self::_LoadNewData();
			self::_SearchCharactersByName();
			
			//Выбираем всех, чьи персонажи нашлись, и кого еще не наградили:
			self::$_votes = Env::Get()->db->Get('game')->Query('
				SELECT
					smv.*,
					c.account
				FROM #site.site_mmotop_votes AS smv
					INNER JOIN characters AS c ON(c.guid = smv.guid)
				WHERE TRUE
					AND smv.paid = 0
					AND smv.guid != 0
			')->FetchAll();
		}
		
		return self::$_votes;
	}
	
	/**
	 * Возвращает время последнего награждения.
	 * @return integer
	 */
	public static function GetLastRewarded()
	{
		$date = Util_Variables::Get(self::LAST_REWARDED_VARIABLE);
		return $date ? strtotime($date) : 0;
	}
	
	/**
	 * Награждает проголосовавших.
	 */
	public static function Reward()
	{
		self::LoadVotes();
		
		$db = Env::Get()->db->Get('game');
		$db->Begin();
		try
		{
			foreach (self::$_votes as $vote)
			{
				//Награды разные в зависимости от типа голоса:
				$value = Env::Get()->config->Get('mmotop/reward/'.(($vote['type'] == self::TYPE_NORMAL) ? 'normal' : 'sms'));
				
				//Увеличиваем баланс:
				$amount = User_Money_Converting::FromMmotop(1);
				User_Money_Cash::Factory($vote['account'])->Change($amount, 'mmotop_vote');
				
				//Ставим пометку, что наградили:
				$db->Query('
					UPDATE #site.site_mmotop_votes AS smv
					SET smv.paid = 1
					WHERE smv.id = :id
				', array(
					'id' => array('d', $vote['id'])
				));
			}
			
			Util_Variables::Set(self::LAST_REWARDED_VARIABLE, date('Y-m-d H:i:s'));
			
			$db->Commit();
			throw new Exception_Http_Redirected();
		}
		catch (Exception_Http_Redirected $e)
		{
			throw $e;
		}
		catch (Exception $e)
		{
			$db->Rollback();
			throw new Exception_UserInput('что-то пошло не так ('.$e->getMessage().')');
		}
	}
};
