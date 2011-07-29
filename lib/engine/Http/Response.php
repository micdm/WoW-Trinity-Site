<?php

/**
 * Сформированный в результате всех действий ответ.
 * @package Http
 * @author Mic, 2010
 */
class Http_Response
{
	/**
	 * Тело ответа.
	 * @var string
	 */
	private static $_body;
	
	/**
	 * Возвращает ключ для кэша.
	 * @return string
	 */
	private static function _GenerateKey()
	{
		$hash = md5(Env::Get()->request->GetUrl());
		return 'html/'.substr($hash, -2).'/'.$hash;
	}
	
	/**
	 * Устанавливает время жизни кэша.
	 * @param integer $period
	 */
	public function SetCacheLifetime($period)
	{
		Http_Cache::SetPeriod($period);
	}
	
	/**
	 * Устанавливает тип содержимого.
	 * @param string $type
	 */
	public function SetContentType($type)
	{
		Http_Header_ContentType::Set($type);
	}
	
	/**
	 * Сохраняет тело ответа.
	 * @param string $content
	 * @return Http_Response
	 */
	public function SetBody($content)
	{
		self::$_body = $content;
		return $this;
	}
	
	/**
	 * Возвращает тело ответа.
	 * @return string
	 */
	public function GetBody()
	{
		return self::$_body;
	}
	
	/**
	 * Сохраняет информацию о странице (тип, содержимое) в кэш.
	 */
	public function StoreOnCache()
	{
		$data = array(
			'type' => Http_Header_ContentType::Get(),
			'body' => self::$_body
		);

		Env::Get()->cache->Save(self::_GenerateKey(), $data);
	}
	
	/**
	 * Загружает страницу из кэша.
	 */
	public function LoadFromCache()
	{
		$data = Env::Get()->cache->Load(self::_GenerateKey(), Http_Cache::GetPeriod());
		if ($data === null)
		{
			throw new Exception_Http_Response_CacheNotFound('страница не найдена в кэше');
		}
		
		//Тип:
		$this->SetContentType($data['type']);
		
		//Содержимое:
		self::$_body = $data['body'];
	}
	
	/**
	 * Выводит содержимое ответа клиенту.
	 */
	public function PrintOutput()
	{
		//Заголовки для кэширования:
		Http_Cache::Run();
		
		//Тип содержимого:
		Http_Header_ContentType::Send();
		
		print(self::$_body);
	}
};
