<?php

/**
 * Базовый класс для всех вьюшек.
 * @author Mic, 2010
 */
class View
{
	/**
	 * Хранилище переменных.
	 * @var Tpl_Context
	 */
	protected $_context;
	
	public function __construct()
	{
		$this->_context = Tpl_Context::Factory();
	}
	
	/**
	 * Запускает метод.
	 * @param string $method
	 * @param array $args
	 * @param array $params
	 * @return string
	 */
	public function RunMethod($method, $args = null, $params = null)
	{
		//Получаем текущее событие:
		$event = Site_Event::GetEventCss();
		if ($event)
		{
			$this->_context->Set('event', $event);
		}
		
		return $this->$method($args, $params);
	}
	
	/**
	 * Возвращает готовый к выводу шаблон.
	 * @param string $template имя шаблона
	 * @param Tpl_Context $context контекст с переменными
	 * @return string
	 */
	protected function _Render($template)
	{
		//Присваиваем переменные:
		if ($this->_context)
		{
			Dev_Debug_Section::Begin('smarty_assign', 'назначение переменных для Smarty');
			Tpl_Smarty_Wrapper::Assign($this->_context);
			Dev_Debug_Section::End();
		}
		
		//Отображаем шаблон:
		Dev_Debug_Section::Begin('smarty_render', 'рендеринг шаблона');
		$output = Tpl_Smarty_Wrapper::Fetch($template);
		Dev_Debug_Section::End();
		
		return $output;
	}
	
	/**
	 * Добавляет сообщение об ошибке.
	 * @param string $name
	 * @param string $msg
	 */
	protected function _AddErrorMsg($name, $msg)
	{
		Tpl_Smarty_Wrapper::_AddErrorMsg($name, $msg);
	}
	
	/**
	 * Добавляет статуное сообщение.
	 * @param string $name
	 * @param string $msg
	 */
	protected function _AddStatusMsg($name, $msg)
	{
		Tpl_Smarty_Wrapper::_AddStatusMsg($name, $msg);
	}
	
	/**
	 * Запускает на выполнение операцию и назначает сообщения об ошибках/успехах.
	 * @param string $name
	 * @return User_Operation_Base
	 */
	protected function _RunOperation($name)
	{
		$operation = null;

		try
		{
			$operation = User_Operation_Base::Factory($name);
			$operation->Run();
		}
		catch (Exception_Http_Redirected $e)
		{
			$msgs = $operation->GetSuccessMessages();
			if ($msgs)
			{
				settype($msgs, 'array');
				foreach ($msgs as $msg)
				{
					$this->_AddStatusMsg($operation->GetCurrentAction(), $msg);
				}
			}

			throw $e;
		}
		catch (Exception_UserInput $e)
		{
			$this->_AddErrorMsg($operation->GetCurrentAction(), $e->getMessage());
		}
		
		return $operation;
	}
};
