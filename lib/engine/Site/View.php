<?php

/**
 * Визуализация самых стартовых страниц.
 * @author Mic, 2010
 */
class Site_View extends View
{
	public function RunMethod($method, $args = null, $params = null)
	{
		//Добавляем время жизни сервера:
		$this->_context->Set('noWipes', Site_Util::CalculateServerLifetime());
		
		return parent::RunMethod($method, $args, $params);
	}
	
	public function Index($args, $params)
	{
		return $this->_Render('main/index.htm');
	}
	
	public function Agreement($args, $params)
	{
		return $this->_Render('main/agreement.htm');
	}
};
