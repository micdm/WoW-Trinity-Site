<?php

class TestHelper_Item
{
	public static function Add($entry)
	{
		Env::Get()->db->Get('game')->Query('
			INSERT INTO #world.item_template (entry)
			VALUES (:entry)
		', array(
			'entry' => array('d', $entry)
		));
	}
};
