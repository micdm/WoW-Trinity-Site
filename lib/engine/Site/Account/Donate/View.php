<?php

/**
 * Визуализация всех страниц про поддержку сервера.
 * @author Mic, 2010
 */
class Site_Account_Donate_View extends Site_Account_View
{
	public function Index($args, $params)
	{
		return $this->_Render('account/donate/index.htm');
	}
	
	public function Mmotop($args, $params)
	{
		return $this->_Render('account/donate/mmotop.htm');
	}
	
	public function Sms($args, $params)
	{
		$this->_context
			->Set('prefix', User_Donating_Sms::GetPrefix())
			->Set('numbers', User_Donating_Sms::GetNumbers());

		return $this->_Render('account/donate/sms.htm');
	}
	
	public function Webmoney($args, $params)
	{
		$this->_context
			->Set('purse', User_Donating_Webmoney::GetPurse())
			->Set('rate', User_Donating_Webmoney::GetRate());
		return $this->_Render('account/donate/webmoney.htm');
	}
	
	public function Items($args)
	{
		$operation = $this->_RunOperation('donating');
		
		$category = $operation->GetCategory(isset($args[0]) ? $args[0] : 0);
		$this->_context
			->Set('items', $operation->GetItems($category, false))
			->Set('category', $category)
			->Set('categories', $operation->GetCategories())
			->Set('subcategories', Env::Get()->config->Get('subcategories/'.$category, 'game'));
		
		return $this->_Render('account/donate/items.htm');
	}
	
	public function Gold($args, $params)
	{
		$operation = $this->_RunOperation('donating');
		$this->_context->Set('rate', User_Money_Converting::GOLD_PER_CHEQUE / 10000);

		return $this->_Render('account/donate/gold.htm');
	}
	
	public function Transfer()
	{
		$this->_RunOperation('donating');
		return $this->_Render('account/donate/transfer.htm');
	}
};
