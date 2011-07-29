<?php

/**
 * Визуализация администраторского раздела.
 * @author Mic, 2010
 */
class Site_Adm_View extends Site_Main_View
{
	/**
	 * Возвращает информацию о последних ошибках.
	 * @return array
	 */
	protected function _GetLastErrors()
	{
		$result = Env::Get()->db->Get('game')->Query('
			SELECT *
			FROM #site.site_log_errors
			ORDER BY id DESC
			LIMIT 20
		')->FetchAll();
		
		foreach ($result as &$error)
		{
			//Распаковываем:
			$error['data'] = unserialize($error['data']);
		}
		
		return $result;
	}
	
	public function RunMethod($method, $args = null, $params = null)
	{
		//Проверяем уровень доступа:
		if (Env::Get()->user->GetAccount()->IsPlayer())
		{
			throw new Exception_Http_Forbidden();
		}

		return parent::RunMethod($method, $args, $params);
	}
	
	public function Index($args, $params)
	{
		throw new Exception_Http_Redirected('/adm/visibility/');
	}
	
	public function Visibility()
	{
		$operation = $this->_RunOperation('masking');
		$this->_context->Set('list', $operation->GetCharacters());
		
		return $this->_Render('adm/visibility.htm');
	}

	public function Graphs($args)
	{
		if (isset($args['type']))
		{
			$result = Site_Adm_Graph::Run($args['type']);
	
			//Отдаем:
			Env::Get()->response->SetContentType('image/png');
			return $result;
		}
		else
		{
			return $this->_Render('adm/graphs.htm');
		}
	}
	
	public function Account()
	{
		$operation = $this->_RunOperation('administration');
		
		$this->_context->Set('account', $operation->GetAccount());
		return $this->_Render('adm/account.htm');
	}
	
	public function Search()
	{
		$operation = $this->_RunOperation('adm_search');

		$this->_context->Set('results', $operation->GetResults());
		return $this->_Render('adm/search.htm');
	}
	
	public function Referrals()
	{
		//Общая статистика:
		$this->_context
			->Set('stats', User_ReferralSystem::GetStats())
			->Set('lastRevarded', User_ReferralSystem::GetLastRewarded());
		
		//Нужно ли начислить:
		if (Env::Get()->request->Post('take'))
		{
			try
			{
				User_ReferralSystem::SendBonuses();
			}
			catch (Exception_Http_Redirected $e)
			{
				$this->_AddStatusMsg('referrals', 'бонусы начислены');
				throw $e;
			}
			catch (Exception_UserInput $e)
			{
				$this->_AddErrorMsg('referrals', $e->getMessage());
			}
		}
		
		return $this->_Render('adm/referrals.htm');
	}
	
	public function Mmotop()
	{
		$this->_context
			->Set('list', array_values(Third_Mmotop::LoadVotes()))
			->Set('lastRevarded', Third_Mmotop::GetLastRewarded());
		
		//Надо наградить:
		if (Env::Get()->request->Post('submit'))
		{
			try
			{
				Third_Mmotop::Reward();
			}
			catch (Exception_Http_Redirected $e)
			{
				$this->_AddStatusMsg('mmotop', 'награды розданы');
				throw $e;
			}
			catch (Exception_UserInput $e)
			{
				$this->_AddErrorMsg('mmotop', $e->getMessage());
			}
		}
		
		return $this->_Render('adm/mmotop.htm');
	}
	
	public function Errors()
	{
		$this->_context->Set('list', $this->_GetLastErrors());
		return $this->_Render('adm/errors.htm');
	}
};
