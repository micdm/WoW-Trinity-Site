<?php

/**
 * Вытаскивание персонажей.
 * @package User_Operation_Action_Rescuing
 * @author Mic, 2010
 */
class User_Operation_Action_Rescuing_Main extends User_Operation_Action_Base
{
	protected function _Setup()
	{
		$this->_AddCharacter('guid', array('mustBelong' => true));
	}
	
	public function GetDescription($accounts, $characters, $plain, $custom)
	{
		$character = $characters['guid'];
		return sprintf('Спасение персонажа %s', $character['name']);
	}

	protected function _DoSomeActions()
	{
		$db = Env::Get()->db->Get('game');
		
		//Очищаем ауры:
		$db->Query('
			DELETE FROM character_aura
			WHERE guid = :guid
		', array(
			'guid' => array('d', $this->GetCharacter()->GetGuid())
		));

		//Обновляем местоположение и снимаем с транспорта:
		$db->Query('
			UPDATE characters AS c, character_homebind AS ch
			SET
				c.position_x = ch.position_x,
				c.position_y = ch.position_y,
				c.position_z = ch.position_z,
				c.map = ch.map,
				c.trans_x = 0,
				c.trans_y = 0,
				c.trans_z = 0,
				c.trans_o = 0,
				c.transguid = 0,
				c.taxi_path = \'\'
			WHERE TRUE
				AND c.guid = :guid
				AND c.guid = ch.guid
		', array(
			'guid' => array('d', $this->GetCharacter()->GetGuid())
		));
		
		//Добавляем штраф:
		$db->Query('
			INSERT INTO character_aura (guid, caster_guid, spell, effect_mask, amount0, maxDuration, remainTime, remainCharges)
			VALUES
				(:guid, :guid, 15007, 0, -75, 900000, 900000, -1),
				(:guid, :guid, 15007, 1, -75, 900000, 900000, -1)
		', array(
			'guid' => array('d', $this->GetCharacter()->GetGuid())
		));
		
		$this->_SetSuccessMessages('персонаж вытащен');
	}
};
