<?php

/**
 * Маршрутизатор, ищет обработчик, который нужно запустить для текущего запроса.
 * @author Mic, 2010
 */
class Routing_Worker
{
	/**
	 * Массив правил, которые будем проверять по очереди.
	 * @var array
	 */
	private static $_rules = array();
	
	/**
	 * Используется при построении правил.
	 * @var string
	 */
	private static $_prefix = '';
	
	/**
	 * Текущая секция.
	 * @var Routing_Section
	 */
	private static $_section;
	
	/**
	 * Результат работы метода.
	 * @var string
	 */
	private static $_result;

	/**
	 * Ищет среди правил первое подходящее и выполняет его.
	 * @return bool
	 */
	private static function _SearchFirstMatched()
	{
		Dev_Debug_Section::Begin('matching', 'поиск подходящего маршрута');
		self::$_section = Routing_Section::Find();
		Dev_Debug_Section::End();
		
		if (self::$_section === null)
		{
			throw new Exception_Http_NotFound('ни одно правило не подошло');
		}
	}
	
	/**
	 * Запускает функцию обратного вызова на выполнение.
	 */
	private static function _RunCallback()
	{
		Dev_Debug_Section::Begin('running_callback', 'выполнение функции обратного вызова');
		
		$callback = self::$_section->GetMethod();
		if (is_array($callback))
		{
			$object = new $callback[0]();
			$method = $callback[1];
		}
		else
		{
			throw new Exception_Runtime('неправильный формат метода, ожидается массив');
		}

		try
		{
			self::_CallMethod($object, $method);
		}
		catch (Exception_Routing_MethodNotFound $e)
		{
			throw new Exception_Http_NotFound();
		}
		
		Dev_Debug_Section::End();
	}
	
	/**
	 * Непосредственно вызывает метод у объекта.
	 * @param stdClass $object
	 * @param string $method
	 */
	protected static function _CallMethod($object, $method)
	{
		$args = self::$_section->GetArgs();
		$params = self::$_section->GetParams();

		if (method_exists($object, 'RunMethod'))
		{
			if (method_exists($object, $method))
			{
				self::$_result = $object->RunMethod($method, $args, $params);
			}
			else
			{
				throw new Exception_Routing_MethodNotFound();
			}
		}
		else if (method_exists($object, $method))
		{
			self::$_result = call_user_func(array($object, $method), $args, $params);
		}
		else
		{
			throw new Exception_Routing_MethodNotFound();
		}
	}
	
	/**
	 * Сохраняем результат.
	 */
	private static function _StoreResult()
	{
		$response = Env::Get()->response;

		//Кэширование:
		$response->SetCacheLifetime();
		
		//Тело:
		$response->SetBody(self::$_result);
	}

	public static function Run()
	{
		//Ищем среди правил первое подходящее:
		self::_SearchFirstMatched();

		//Время жизни кэша:
		$response = Env::Get()->response;
		$response->SetCacheLifetime(self::$_section->GetCacheLifetime());
		
		//Если нужно серверное кэширование страниц, работаем с кэшем:
		if (self::$_section->IsServerCacheNeeded())
		{
			try
			{
				//Пытаемся загрузить из кэша:
				$response->LoadFromCache();
			}
			catch (Exception_Http_Response_CacheNotFound $e)
			{
				//Данных в кэше нет, генерируем новые.
				//Запускаем вызов и сохраняем результат:
				self::_RunCallback();
				$response
					->SetBody(self::$_result)
					->StoreOnCache();
			}			
		}
		else
		{
			//Кэш не нужен, запускаем вызов напрямую:
			self::_RunCallback();
			$response->SetBody(self::$_result);
		}
	}
};
