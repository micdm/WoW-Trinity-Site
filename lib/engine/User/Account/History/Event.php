<?php

/**
 * Одно событие из истории.
 * @package User_Account
 * @author Mic, 2010
 */
class User_Account_History_Event extends ArrayType
{
	/**
	 * Время совершения события.
	 * @var string
	 */
	protected $_time;
	
	/**
	 * IP-адрес, с которого состоялось событие.
	 * @var string
	 */
	protected $_ip;
	
	/**
	 * Название операции.
	 * @var string
	 */
	protected $_operation;
	
	/**
	 * Название действия.
	 * @var string
	 */
	protected $_action;
	
	/**
	 * Описание события.
	 * @var string
	 */
	protected $_description;
	
	/**
	 * Список участвующих аккаунтов.
	 * @var array
	 */
	protected $_accounts;
	
	/**
	 * Список участвующих персонажей.
	 * @var array
	 */
	protected $_characters;
	
	/**
	 * Список простых полей.
	 * @var array
	 */
	protected $_plain;
	
	/**
	 * Список дополнительных полей.
	 * @var array
	 */
	protected $_custom;
	
	/**
	 * Выбирает и сохраняет новые данные.
	 * @param array $data
	 */
	public function Update($data)
	{
		$this->_time = $data['created'];
		$this->_ip = $data['ip'];
		$this->_operation = $data['operation'];
		$this->_action = $data['action'];
		
		if ($data['a_account_id'] !== null)
		{
			$this->_accounts[$data['a_field']] = $data['a_account_id'];
		}
		
		if ($data['ch_guid'] !== null && $data['ch_name'] !== null)
		{
			$this->_characters[$data['ch_field']] = array(
				'guid' => $data['ch_guid'],
				'name' => $data['ch_name'],
			);
		}
		
		if ($data['p_name'] !== null && $data['p_value'] !== null)
		{
			$this->_plain[$data['p_name']] = $data['p_value'];
		}
		
		if ($data['cu_name'] !== null && $data['cu_value'] !== null)
		{
			$this->_custom[$data['cu_name']] = $data['cu_value'];
		}
	}
	
	/**
	 * @return string
	 */
	public function GetTime()
	{
		return $this->_time;
	}
	
	/**
	 * @return string
	 */
	public function GetIp()
	{
		return $this->_ip;
	}
	
	/**
	 * @return string
	 */
	public function GetDescription()
	{
		if ($this->_description === null)
		{
			$operation = User_Operation_Base::Factory($this->_operation);
			$this->_description = $operation->GetDescription($this->_action, $this->_accounts, $this->_characters,
				$this->_plain, $this->_custom);
		}

		return $this->_description;
	}
};
