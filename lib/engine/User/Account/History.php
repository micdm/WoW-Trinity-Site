<?php

/**
 * История операций с аккаунтом.
 * @package User_Account
 * @author Mic, 2010
 */
class User_Account_History extends ArrayType
{
	/**
	 * Идентификатор аккаунта.
	 * @var integer
	 */
	protected $_account;
	
	/**
	 * Список операций.
	 * @var array
	 */
	protected $_list;
	
	/**
	 * @param integer $account
	 */
	public function __construct($account)
	{
		$this->_account = $account;
	}
	
	/**
	 * Загружает список операций.
	 */
	protected function _LoadList()
	{
		$this->_list = Env::Get()->cache->Load('account/history/'.$this->_account, 600);
		if ($this->_list)
		{
			return;
		}
		
		$data = Env::Get()->db->Get('game')->Query('
			SELECT
				h.*,
				a.field AS a_field, a.account_id AS a_account_id,
				ch.field AS ch_field, ch.guid AS ch_guid, ch.name AS ch_name,
				p.name AS p_name, p.value AS p_value,
				cu.name AS cu_name, cu.value AS cu_value
			FROM #site.site_operation_history AS h
				LEFT JOIN #site.site_operation_history_accounts AS a ON(a.history_id = h.id)
				LEFT JOIN #site.site_operation_history_characters AS ch ON(ch.history_id = h.id)
				LEFT JOIN #site.site_operation_history_plain AS p ON(p.history_id = h.id)
				LEFT JOIN #site.site_operation_history_custom AS cu ON(cu.history_id = h.id)
			WHERE h.account = :account
			ORDER BY h.created DESC
			LIMIT 50
		', array(
			'account' => array('d', $this->_account)
		))->FetchAll();
		
		$this->_list = array();
		foreach ($data as $row)
		{
			$id = $row['id'];
			if (isset($this->_list[$id]) == false)
			{
				$this->_list[$id] = new User_Account_History_Event();
			}
			
			$this->_list[$id]->Update($row);
		}
		
		Env::Get()->cache->Save(null, $this->_list);
	}
	
	/**
	 * Возвращает список операций.
	 * @return array
	 */
	public function GetList()
	{
		if ($this->_list === null)
		{
			$this->_LoadList();
		}

		return $this->_list;
	}
};
