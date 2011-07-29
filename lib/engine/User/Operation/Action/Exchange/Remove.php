<?php

/**
 * Отмена своей заявки на обмен.
 * @package User_Operation_Action_Exchange
 * @author Mic, 2010
 */
class User_Operation_Action_Exchange_Remove extends User_Operation_Action_Base
{
	protected function _Setup()
	{
		$this->_AddPlainField('ids');
	}
	
	public function GetDescription($accounts, $characters, $plain, $custom)
	{
		return sprintf('Удаление заявки(ок) на обмен персонажами');
	}
	
	protected function _CheckPlainFields()
	{
		parent::_CheckPlainFields();
		
		$ids = $this->GetPlainField();
		
		//Ожидаем получить массив:
		if (is_array($ids) == false)
		{
			throw new Exception_Runtime('ожидается массив, '.gettype($ids).' получен');
		}
		
		//Непустой массив:
		if (count($ids) == 0)
		{
			throw new Exception_Runtime('ожидается непустой массив');
		}
		
		//Преобразовываем к целым числам и Убираем дубликаты:
		$this->_plain['ids'] = array_unique(array_map('intval', $ids));
	}
	
	protected function _CheckAdditionalConditions()
	{
		//Проверяем, что каждая заявка принадлежит аккаунту:
		$guids = Env::Get()->db->Get('game')->Query('
			SELECT guid_my
			FROM #site.site_operation_exchange
			WHERE id IN('.implode(',', $this->GetPlainField()).')
		')->FetchColumn();
		
		$myGuids = Env::Get()->user->GetAccount()->GetCharacters()->GetGuids();
		foreach ($guids as $guid)
		{
			if (in_array($guid, $myGuids) == false)
			{
				throw new Exception_User_Operation_BadCondition('Вы не можете отменять чужие заявки');
			}
		}
	}
	
	protected function _DoSomeActions()
	{
		//Удаляем заявки и запоминаем их количество (понадобится потом, чтобы вывести сообщение):
		$count = Env::Get()->db->Get('game')->Query('
			DELETE FROM #site.site_operation_exchange
			WHERE id IN('.implode(',', $this->GetPlainField()).')
		')->GetRowsCount();
		
		$this->_SetSuccessMessages(($count == 0) ? 'ни одной заявки не отменено' : (($count == 1) ? 'заявка отменена' : 'заявки отменены'));
	}
};
