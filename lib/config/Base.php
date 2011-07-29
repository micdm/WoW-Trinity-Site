<?php

/**
 * Хранилище всех базовых настроек.
 * @author Mic, 2010
 */
class Config_Base
{
	public static function GetRewrites()
	{
		//Настройки БД:
		if (Env::Get()->debug->IsTesting())
		{
			$game = 'db/dev';
			$site = 'db/site_dev';
		}
		else if (Env::Get()->debug->IsLocal())
		{
			$game = 'db/game';
			$site = 'db/site_dev';
		}
		else
		{
			$game = 'db/game';
			$site = 'db/site';
		}
		
		return array(
			'db/game' => $game,
			'db/site' => $site
		);
	}
	
	/**
	 * Таймзона сервера.
	 * @var string
	 */
	public static $timezone = 'Asia/Novosibirsk';

	/**
	 * Описание баз данных.
	 * @var array
	 */
	public static $db = array(
		'game' => array(
			'host' => 'localhost',
			'port' => '3306',
			'user' => 'root',
			'pass' => '',
			'name' => 'characters335',
			
			'extra' => array(
				'realm' => 'realmd335',
				'world' => 'world335',
				'site' => 'site_current'
			)
		),
		
		'site' => array(
			'host' => 'localhost',
			'port' => '3306',
			'user' => 'root',
			'pass' => '',
			'name' => 'forum'
		),

		'site_dev' => array(
			'host' => 'localhost',
			'port' => '3306',
			'user' => 'root',
			'pass' => '',
			'name' => 'forum'
		),
		
		'dev' => array(
			'host' => 'localhost',
			'port' => '3306',
			'user' => 'root',
			'pass' => '',
			'name' => 'wow_characters',
		
			'extra' => array(
				'realm' => 'wow_realmd',
				'world' => 'wow_world',
				'site' => 'wow_site'
			)
		)
	);

	/**
	 * Описание игровых серверов.
	 * @var array
	 */
	public static $server = array(
		'host' => '217.29.86.182',
		'port' => '8085'
	);
	
	/**
	 * Идентификатор основного рилма.
	 * @var integer
	 */
	public static $realm = 1;
	
	/**
	 * Шаблоны для регистрации.
	 * @var array
	 */
	public static $patterns = array(
		'username' => '#^[a-zA-Z0-9]{3,32}$#',
		'password' => '#^[a-zA-Z0-9!"\#$%]{3,16}$#'
	);
	
	/**
	 * Время создания сервера.
	 * @var integer
	 */
	public static $bornTime = 1145935136;

	/**
	 * Информация для отправки писем.
	 * @var array
	 */
	public static $mail = array(
		//Метод отправки писем. Возможные значения "mail" и "smtp":
		'method' => 'mail',
	
		//Для заголовков про отправителя:
		'from' => array(
			//Адрес почты:
			'address' => 'site@example.com',
	
			//Имя:
			'name' => 'Wow Trinity Site',
		),
		
		//Данные для авторизации на SMTP-сервере:
		'smtp' => array(
			'host' => 'example.com',
			'port' => '587',
			'username' => 'username',
			'password' => 'password',
		)
	);
	
	/**
	 * Список событий, от которых зависит оформление сайта.
	 * @var array
	 */
	public static $events = array(
		/*
		'march8' => array(
			'start' => '08 march',
			'finish' => '09 march',
			'css' => array(
				'events/march8/base.css',
				'events/march8/charlist.css',
			),
			'tip' => array(
				'link' => '',
				'text' => 'Поздравляем милых дам!'
			)
		)
		*/
	);
	
	/**
	 * Версия клиента.
	 * @var string
	 */
	public static $client = '3.3.5a';
	
	/**
	 * Рейты.
	 * @var string
	 */
	public static $rates = '1.5';
	
	/**
	 * Информация для подключения к Trinity-консоли.
	 * @var array
	 */
	public static $console = array(
		'host' => 'example.com',
		'port' => '3443',
		'user' => 'user',
		'password' => 'password'
	);
	
	/**
	 * Можно ли держать на аккаунте персонажей обоих фракций?
	 * @var bool
	 */
	public static $canHaveBothFactions = true;
	
	/**
	 * Максимальное количество персонажей, которое может быть на аккаунте.
	 * @var integer
	 */
	public static $charactersOnAccountMaxCount = 8;
	
	/**
	 * Минимальный уровень персонажа, который может создать рыцаря смерти.
	 * @var integer
	 */
	public static $minLevelForDeathknight = 55;
	
	/**
	 * Информация для взаимодействия с mmotop.ru.
	 * @var array
	 */
	public static $mmotop = array(
		'id' => 0,
		'votes' => 'путь к файлу со статистикой',
		'reward' => array(
			'normal' => 0.5,
			'sms' => 0.5,
		)
	);
	
	/**
	 * Настройки для взаимодействия с форумом.
	 * @var array
	 */
	public static $forum = array(
		//Реализующий класс:
		'class' => 'Third_Forum_Phpbb3',
	
		//Адрес форума (слеш в конце нужен):
		'address' => 'forum.example.com',
	
		//Идентификатор топика с анонсами:
		'announcesTopicId' => 0,
	
		//Идентификатор топика, в котором лежат новости:
		'newsTopicId' => 0,
	
		//Идентификаторы форумов, сообщения с которых не нужно показывать:
		'excludeForums' => array(),

		//Идентификатор форума с темами про обмен:
		'exchangeForumId' => 0,
	
		//Показывать ли в последних сообщениях что-то про обмен?
		'excludeExchangeMessages' => true,
	);
	
	/**
	 * Метод отправки писем в игру:
	 * "console" - отправка через Trinity-консоль;
	 * "external" - отправка через mail_external.
	 * @var string
	 */
	public static $gameMailMethod = 'console';
	
	/**
	 * Временной интервал между двумя регистрациями с одного IP-адреса (в секундах).
	 * Если время не вышло, потребуем капчу.
	 * @var integer
	 */
	public static $periodBetweenRegistrations = 3600;
	
	/**
	 * Период в секундах, в течение которого будет работать премиум-система.
	 * Если 0 - выключено.
	 * @var integer
	 */
	public static $premiumAccountPeriod = 1209600;
	
	/**
	 * Номер кошелька для переводов.
	 * @var string
	 */
	public static $webmoneyPurse = 'адрес кошелька';
	
	/**
	 * Настройки SMS-донейта.
	 * @var array
	 */
	public static $sms = array(
		//Секретный ключ:
		'key' => 'key',
	
		//Префикс для сообщений:
		'prefix' => 'prefix',
	);
	
	/**
	 * Расценки на сервисы сайта.
	 * @var array
	 */
	public static $operations = array(
		'exchange' => array(
			'main' => array(
				'gold' => 5000000,
			),
		),
		'makeuping' => array(
			'main' => array(
				'gold' => 50000000,
			),
		),
		'renaming' => array(
			'main' => array(
				'gold' => 10000000,
			),
		),
		'transfer' => array(
			'main' => array(
				'gold' => 5000000,
			),
		),
	);
};
