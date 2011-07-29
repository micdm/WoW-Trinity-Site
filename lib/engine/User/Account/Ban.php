<?php

/**
 * Один бан аккаунта.
 * @package User_Account
 * @author Mic, 2010
 */
class User_Account_Ban extends ArrayType
{
	/**
	 * Дата бана.
	 * @var string
	 */
	protected $_created;
	
	/**
	 * Дата разбана.
	 * @var string
	 */
	protected $_expires;
	
	/**
	 * Имя автора бана.
	 * @var string
	 */
	protected $_author;
	
	/**
	 * Причина.
	 * @var string
	 */
	protected $_reason;
	
	/**
	 * Уже снят?
	 * @var boolean
	 */
	protected $_isActive;
	
	/**
	 * @param array $data
	 */
	public function __construct($data)
	{
		$this->_created = $data['bandate'];
		$this->_expires = $data['unbandate'];
		$this->_author = $data['bannedby'];
		$this->_reason = $data['banreason'];
		$this->_isActive = ($data['active'] == 1);
	}
	
	/**
	 * Возвращает дату бана.
	 * @return string
	 */
	public function GetCreated()
	{
		return $this->_created;
	}

	/**
	 * Возвращает дату разбана.
	 * @return string
	 */
	public function GetExpires()
	{
		return $this->_expires;
	}

	/**
	 * Возвращает имя автора.
	 * @return string
	 */
	public function GetAuthor()
	{
		return $this->_author;
	}

	/**
	 * Возвращает причину.
	 * @return string
	 */
	public function GetReason()
	{
		return $this->_reason;
	}

	/**
	 * Активен ли бан?
	 * @return string
	 */
	public function IsActive()
	{
		return $this->_isActive;
	}
	
	/**
	 * Является ли бан постоянным?
	 * @return boolean
	 */
	public function IsPermanent()
	{
		return $this->_created == $this->_expires;
	}
};
