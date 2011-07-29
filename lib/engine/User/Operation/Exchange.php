<?php

/**
 * Сервис обмена персонажами.
 * @package User_Operation
 * @author Mic, 2010
 */
class User_Operation_Exchange extends User_Operation_Base
{
	protected function _Setup()
	{
		$this->_AddAction('main', 'Exchange_Main');
		$this->_AddAction('remove', 'Exchange_Remove');
		$this->_AddAction('accept', 'Exchange_Accept');
	}
	
	/**
	 * Возвращает список заявок в зависимости от выбранного поля.
	 * @param string $field
	 */
	private function _GetRequests($field)
	{
		$result = array();
		
		$guids = Env::Get()->user->GetAccount()->GetCharacters()->GetGuids();
		if ($guids)
		{
			$result = Env::Get()->db->Get('game')->Query('
				SELECT
					oe.id,
				
					c1.guid AS myGuid,
					c1.name AS myName,
					c1.race AS myRace,
					c1.gender AS myGender,
					c1.class AS myClass,
					c1.level AS myLevel,
					
					c2.guid AS itsGuid,
					c2.name AS itsName,
					c2.race AS itsRace,
					c2.gender AS itsGender,
					c2.class AS itsClass,
					c2.level AS itsLevel
				FROM #site.site_operation_exchange AS oe
					INNER JOIN characters AS c1 ON(c1.guid = oe.guid_my)
					INNER JOIN characters AS c2 ON(c2.guid = oe.guid_its)
				WHERE TRUE
					AND oe.'.$field.' IN('.implode(',', $guids).')
				ORDER BY
					c1.name,
					c2.name
			')->FetchAll();
		}
		
		return $result;
	}
	
	/**
	 * Возвращает список исходящих заявок.
	 * @return array
	 */
	public function GetOutcoming()
	{
		return $this->_GetRequests('guid_my');
	}
	
	/**
	 * Возвращает список входящих заявок.
	 * @return array
	 */
	public function GetIncoming()
	{
		return $this->_GetRequests('guid_its');
	}
};
