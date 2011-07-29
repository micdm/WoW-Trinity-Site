<?php

class Site_Adm_Donate_View extends Site_Adm_View
{
	public function RunMethod($method, $args = null, $params = null)
	{
		//Проверяем уровень доступа:
		if (Env::Get()->user->GetAccount()->IsAdministrator() == false)
		{
			throw new Exception_Http_Forbidden();
		}

		return parent::RunMethod($method, $args, $params);
	}
	
	public function Index($args, $params)
	{
		return $this->_Render('adm/donate/index.htm');
	}
	
	public function Webmoney($args, $params)
	{
		$this->_RunOperation('administration');
		
		$this->_context->Set('rate', User_Donating_Webmoney::GetRate());
		return $this->_Render('adm/donate/webmoney.htm');
	}
	
	public function Items($args)
	{
		$operation = $this->_RunOperation('donating');
		
		$category = $operation->GetCategory(isset($args[0]) ? $args[0] : 0);
		$this->_context
			->Set('items', $operation->GetItems($category, true))
			->Set('category', $category)
			->Set('categories', $operation->GetCategories())
			->Set('subcategories', Env::Get()->config->Get('subcategories/'.$category, 'game'));

		return $this->_Render('adm/donate/items.htm');
	}
};
