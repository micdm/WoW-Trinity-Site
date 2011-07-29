<?php

/**
 * Скрытие/отображение гейммастеров.
 * @package User_Operation_Action_Masking
 * @author Mic, 2010
 */
class User_Operation_Action_Masking_Main extends User_Operation_Action_Base
{
	protected function _Setup()
	{
		$this->_AddPlainField('list', array(
			'datatype' => 'array',
			'canBeNull' => true
		));
	}
	
	public function GetDescription($accounts, $characters, $plain, $custom)
	{
		return 'Переключение невидимости у ГМ-персонажей';
	}
	
	protected function _CheckAdditionalConditions()
	{

	}

	protected function _DoSomeActions()
	{
		//Идем по списку персонажей аккаунта, и если какие-то из них есть в пришедшем списке,
		//добавляем их в список видимых.
		$db = Env::Get()->db->Get('game');
		foreach (Env::Get()->user->GetAccount()->GetCharacters()->GetAll() as $character)
		{
			if (in_array($character->GetGuid(), $this->GetPlainField()))
			{
				$db->Query('
					INSERT IGNORE INTO #site.site_operation_masking (guid)
					VALUES (:guid)
				', array(
					'guid' => array('d' , $character->GetGuid())
				));
			}
			else
			{
				$db->Query('
					DELETE FROM #site.site_operation_masking
					WHERE guid = :guid
				', array(
					'guid' => array('d', $character->GetGuid())
				));
			}
		}
	}
};
