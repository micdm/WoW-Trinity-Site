<?php

include_once('_env.php');
include_once('_autoload.php');

//Инициируем обработчик стандартных ошибок:
Error::Init();

//Устанавливаем таймзону:
date_default_timezone_set(Env::Get()->config->Get('timezone'));

//Для командной строки сайт не инициализируем:
if (IS_CL == false)
{
	Init::Run();
}
