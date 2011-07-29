<?php

/**
 * Пользовательское соглашение.
 * @author Mic, 2010
 */
class Site_Agreement
{
	/**
	 * Интервал в секундах, после которого придется снова принимать соглашение.
	 * @var integer
	 */
	const AGREEMENT_EXPIRE_TIME = 30758400;
	
	/**
	 * Фрагменты адресов, для которых не нужно показывать пользовательское соглашение.
	 * @var array
	 */
	private static $_excludes = array(
		'#^/agreement/#',
		'#^/$#',
		'#^/internal/#',
		'#^/userbar/#',
		'#^/gates/#'
	);
	
	/**
	 * Проверяет, нужно ли для адреса показать соглашение. 
	 * @return bool
	 */
	private static function _IsExcluded()
	{
		foreach (self::$_excludes as $fragment)
		{
			if (preg_match($fragment, Env::Get()->request->GetUrl()) == 1)
			{
				return true;
			}
		}
		
		return false;
	}
	
	public static function Init()
	{
		//Проверяем, принимает ли пользователь соглашение:
		if (Env::Get()->request->Post('accept_agreement'))
		{
			//Ставим куку:
			Util_Cookie::Set('accepted', 1, time() + self::AGREEMENT_EXPIRE_TIME, '/');

			//Перекидываем обратно:
			$request = Env::Get()->request;
			if ($request->Get('back'))
			{
				throw new Exception_Http_Redirected(urldecode($request->Get('back')));
			}
		}
		else if (empty($_COOKIE['accepted']) && self::_IsExcluded() == false)
		{
			//Куки нет, соглашение надо принимать, - перекидываем:
			throw new Exception_Http_Redirected('/agreement/?back='.urlencode(Env::Get()->request->GetUrl()));
		}
	}
};
