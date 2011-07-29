<?php

/**
 * Центральная часть сайта, сквозь которую проходят все запросы.
 * @author Mic, 2010
 */
class Init
{
	/**
	 * Перенаправляет пользователя на другой адрес.
	 * @param string $address
	 */
	private static function _DoRedirect($address)
	{
		//Перенаправление не на внешний адрес:
		if (strpos($address, 'http://') !== 0)
		{
			//Перенаправление на ту же страницу:
			if (empty($address))
			{
				$address = Env::Get()->request->GetUrl();
			}
			
			$address = Env::Get()->request->GetAbsoluteUrl($address, true);
		}
		
		//При отладке не перенаправляем автоматически:
		if (Env::Get()->debug->IsActive() == false)
		{
			header('Location: '.$address);
		}
	}
	
	/**
	 * Запускает обычный сценарий работы и обрабатывает HTTP-коды.
	 */
	private static function _RunNormalActions()
	{
		try
		{
			//Инициируем пользовательское соглашение:
			//Site_Agreement::Init();
			
			//Запускаем маршрутизатор:
			Dev_Debug_Section::Begin('routing', 'полное время работы маршрутизатора');
			Routing_Worker::Run();
			Dev_Debug_Section::End();

			//Выводим результат:
			Env::Get()->response->PrintOutput();
		}
		catch (Exception_Http_Redirected $e)
		{
			//Нужно идти в другое место:
			self::_DoRedirect($e->getMessage());
		}
		catch (Exception_Http_Forbidden $e)
		{
			//Доступ запрещен:
			$view = new Site_Internal_View();
			print($view->RunMethod('Page403'));
		}
		catch (Exception_Http_NotFound $e)
		{
			//Страница не найдена:
			$view = new Site_Internal_View();
			print($view->RunMethod('Page404'));
		}
		catch (Exception_Http_Unavailable $e)
		{
			//Сервер временно не работает:
			$view = new Site_Internal_View();
			print($view->RunMethod('Page503'));
		}

		//Бросаем дальше:
		if (Env::Get()->debug->IsActive() && isset($e))
		{
			throw $e;
		}
	}
	
	public static function Run()
	{
		Dev_Debug_Section::Begin('main', 'полное время работы');
		
		try
		{
			ob_start();
			self::_RunNormalActions();
			ob_end_flush();
		}
		catch (Exception $e)
		{
			//очищаем все буферы, который могли накопиться:
			while (ob_get_level())
			{
				ob_end_clean();
			}
			
			if (Env::Get()->debug->IsActive())
			{
				//При отладке распечатываем ошибки:
				Http_Header_ContentType::Set('text/plain');
				Http_Header_ContentType::Send();
				
				//Печатаем некоторую информацию:
				print(strval($e));
			}
			else
			{
				//Пытаемся записать в лог:
				try
				{
					Env::Get()->log->Add('errors', strval($e));
				}
				catch (Exception $e)
				{
					
				}
				
				//В обычном режиме показываем спецстраничку:
				$view = new Site_Internal_View();
				print($view->RunMethod('Page500'));
			}
		}
		
		Dev_Debug_Section::End();
		
		//Отладочная информация:
		if (Env::Get()->debug->IsActive())
		{
			Env::Get()->debug->ShowInfo();
		}
	}
};
