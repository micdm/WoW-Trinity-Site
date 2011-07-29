<?php

/**
 * Генрация капчи для регистрации.
 * @author Mic, 2010
 */
class Site_Captcha
{
	public function Index()
	{
		//Генерируем картинку (буферизуем, чтобы иметь возможность отправить сессионную куку):
		ob_start();
		$captcha = new KCAPTCHA();
		$image = ob_get_clean();
		
		//Сохраняем в сессии ключ:
		self::SetCode($captcha->getKeyString());
		
		print($image);
	}
	
	/**
	 * Сохраняет код в сессии.
	 * @param string $value
	 */
	public static function SetCode($value = null)
	{
		Env::Get()->session->Set('captcha', $value);
	}
	
	/**
	 * Проверяет, соответствует ли код сохраненному в сессии.
	 * @param string $value
	 * @return bool
	 */
	public static function IsValidCode($value)
	{
		return (Env::Get()->session->Get('captcha') === $value);
	}
};
