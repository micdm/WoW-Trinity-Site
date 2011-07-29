<?php

/**
 * Вспомогательные функции для работы с аккаунтом.
 * @author Mic, 2010
 */
class TestHelper_Account
{
	/**
	 * Добавляет тестовый аккаунт.
	 * @param integer $id
	 * @param string $name
	 * @return User_Account
	 */
	public static function Add($id, $name = 'foo', $isAdmin = false)
	{
		Env::Get()->db->Get('game')->Query('
			INSERT INTO #realm.account (id, username, sha_pass_hash, email)
			VALUES (:id, :username, SHA1(UPPER(CONCAT(:username, \':\', :password))), :email)
		', array(
			'id' => array('d', $id),
			'username' => array('s', $name),
			'password' => array('s', 'bar'),
			'email' => array('s', $name.'@example.com'),
		));
		
		//Делаем администратором:
		if ($isAdmin)
		{
			Env::Get()->db->Get('game')->Query('
				INSERT INTO #realm.account_access (id, gmlevel, realmId)
				VALUES (:id, :level, -1)
			', array(
				'id' => array('d', $id),
				'level' => array('s', User_Account::LEVEL_ADMINISTRATOR),
			));
		}
		
		return User_Account::Factory($id);
	}
	
	/**
	 * Добавляет для аккаунта запись, что он забанен.
	 * @param integer $id
	 */
	public static function Ban($id)
	{
		Env::Get()->db->Get('game')->Query('
			INSERT INTO #realm.account_banned (id)
			VALUES (:id)
		', array(
			'id' => array('d', $id)
		));
	}
};
