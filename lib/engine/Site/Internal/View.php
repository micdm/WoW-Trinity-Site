<?php

/**
 * Визуализация специальных страниц.
 * @author Mic, 2010
 */
class Site_Internal_View extends View
{
	public function Page403()
	{
		return $this->_Render('internal/403.htm');
	}
	
	public function Page404()
	{
		return $this->_Render('internal/404.htm');
	}
	
	public function Page500()
	{
		return $this->_Render('internal/500.htm');
	}
	
	public function Page503()
	{
		return $this->_Render('internal/503.htm');
	}
};
