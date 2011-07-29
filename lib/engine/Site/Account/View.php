<?php

/**
 * Визуализация всех разделов про аккаунт.
 * @author Mic, 2010
 */
class Site_Account_View extends Site_Main_View
{
	public function RunMethod($method, $args = null, $params = null)
	{
		if (Env::Get()->user->IsGuest())
		{
			//Если пользователь не авторизован, показываем ему страницу входа:
			$method = 'Login';
		}
		else
		{
			$this->_context->Set('balance', Env::Get()->user->GetCash()->Get());
		}

		return parent::RunMethod($method, $args, $params);
	}
	
	public function Index($args, $params)
	{
		$this->_RunOperation('setting');
		return $this->_Render('account/index.htm');
	}
	
	public function Login()
	{
		$this->_RunOperation('login');
		return $this->_Render('account/login.htm');
	}
	
	public function Characters($args, $params)
	{
		$operation = $this->_RunOperation('rescuing');
		$this->_context->Set('list', Env::Get()->user->GetAccount()->GetCharacters()->GetAll());
		return $this->_Render('account/characters.htm');
	}

	public function Rename($args, $params)
	{
		$operation = $this->_RunOperation('renaming');
		$this->_context
			->Set('list', $operation->GetCharacters())
			->Set('price', $operation->GetPrice());

		return $this->_Render('account/rename.htm');
	}
	
	public function Appearance($args, $params)
	{
		$operation = $this->_RunOperation('makeuping');
		$this->_context
			->Set('list', $operation->GetCharacters())
			->Set('price', $operation->GetPrice());
		
		return $this->_Render('account/appearance.htm');
	}
	
	public function Transfer()
	{
		$operation = $this->_RunOperation('transfer');
		$this->_context
			->Set('list', Env::Get()->user->GetAccount()->GetCharacters()->GetAll())
			->Set('price', $operation->GetPrice());
			
		return $this->_Render('account/transfer.htm');
	}
	
	public function Exchange()
	{
		$operation = $this->_RunOperation('exchange');
		
		$this->_context
			->Set('list', Env::Get()->user->GetAccount()->GetCharacters()->GetAll())
			->Set('outcoming', $operation->GetOutcoming())
			->Set('incoming', $operation->GetIncoming())
			->Set('price', $operation->GetPrice());
		
		return $this->_Render('account/exchange.htm');
	}
	
	public function Referrals($args, $params)
	{
		$this->_context->Set('list', Env::Get()->user->GetAccount()->GetCharacters()->GetAll());
		$this->_context->Set('referrals', User_ReferralSystem::GetReferrals());
		$this->_context->Set('money', User_ReferralSystem::GetMoneyGained());
		return $this->_Render('account/referrals.htm');
	}
	
	public function Userbars($args, $params)
	{
		$this->_context->Set('list', Env::Get()->user->GetAccount()->GetCharacters()->GetAll());
		return $this->_Render('account/userbars.htm');
	}
	
	public function History()
	{
		$this->_context->Set('history', Env::Get()->user->GetAccount()->GetHistory());
		return $this->_Render('account/history.htm');
	}
	
	public function Logout($args, $params)
	{
		$this->_RunOperation('logout');
		
		//Сюда не должны добраться в нормальном случае: сработает переадресация раньше.
		//Перенаправляем на главную страницу аккаунта:
		throw new Exception_Http_Redirected('/account/');
	}
};
