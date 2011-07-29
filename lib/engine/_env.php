<?php

//Запускаем ли из командной строки?
define('IS_CL', empty($_SERVER['SERVER_NAME']));

//Путь относительно корня сайта:
if (IS_CL || $_SERVER['REMOTE_ADDR'] == '127.0.0.1')
{
	define('SITE_RELATIVE_ROOT', '');
	define('DOCUMENT_ROOT', '/home/www/php/wow/trunk/');
}
else
{
	define('SITE_RELATIVE_ROOT', '');
	define('DOCUMENT_ROOT', $_SERVER['DOCUMENT_ROOT'].'/');
}

//Абсолютный путь в файловой системе до корня сайта:
define('SITE_ROOT', DOCUMENT_ROOT.'site/');

//Путь к скриптам:
define('LIB_ROOT', SITE_ROOT.'lib/');

//Путь к конфигам:
define('CONFIG_ROOT', LIB_ROOT.'config/');

//Путь к скриптам движка:
define('ENGINE_ROOT', LIB_ROOT.'engine/');

//Путь к шаблонам:
define('TEMPLATES_ROOT', SITE_ROOT.'tpl/');

//Путь к кэшу:
define('CACHE_ROOT', SITE_ROOT.'cache/');

//Путь к различным нужным файлам:
define('STUFF_ROOT', SITE_ROOT.'stuff/');

//Путь к юнит-тестам:
define('UNIT_TESTS_ROOT', SITE_ROOT.'tests/');

//Путь к логам:
define('LOGS_ROOT', SITE_ROOT.'log/');

//Абсолютный путь к aowow и путь относительно корня сайта:
define('AOWOW_ROOT', DOCUMENT_ROOT.'aowow/');
define('AOWOW_RELATIVE_ROOT', '/aowow/');
