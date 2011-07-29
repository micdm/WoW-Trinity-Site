<?php

class TestHelper_Character
{
	public static function Add($guid, $name = 'foo', $account = 0, $additional = null)
	{
		//Добавляем аккаунт:
		Env::Get()->db->Get('game')->Query('
			INSERT INTO characters (guid, name, account, online, money, at_login, race, class, level)
			VALUES (:guid, :name, :account, :online, :money, :at_login, :race, :class, :level)
		', array(
			'guid' => array('d', $guid),
			'name' => array('s', $name),
			'account' => array('d', $account),
			'online' => array('d', isset($additional['online']) ? $additional['online'] : 0),
			'money' => array('d', isset($additional['money']) ? $additional['money'] : 0),
			'at_login' => array('d', isset($additional['atLogin']) ? $additional['atLogin'] : 0),
			'race' => array('d', isset($additional['race']) ? $additional['race'] : 0),
			'class' => array('d', isset($additional['class']) ? $additional['class'] : 0),
			'level' => array('d', isset($additional['level']) ? $additional['level'] : 0),
		));
	}
};
