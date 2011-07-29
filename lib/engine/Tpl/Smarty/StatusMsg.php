<?php

/**
 * Статусное сообщение для пользователя.
 * @author Mic, 2010
 * @package Tpl_Smarty
 */
class Tpl_Smarty_StatusMsg
{
	/**
	 * Загружает сообщения из хранилища по имени.
	 * @param string $name
	 * @return array
	 */
	private static function _Load($name)
	{
		$msgs = Env::Get()->session->Get('msgs');
		$url = Env::Get()->request->GetUrl();
		return empty($msgs[$url][$name]) ? array() : $msgs[$url][$name];
	}
	
	/**
	 * Сохраняет сообщения в хранилище.
	 * @param string $name
	 * @param array $list
	 */
	private static function _Save($name, $list)
	{
		$msgs = Env::Get()->session->Get('msgs');
		$url = Env::Get()->request->GetUrl();
		$msgs[$url][$name] = $list;
		Env::Get()->session->Set('msgs', $msgs);
	}
	
	/**
	 * Проверяет, есть ли уже такое сообщение в списке сохраненных.
	 * @param string $name
	 * @param string $msg
	 * @return boolean
	 */
	private static function _IsStored($name, $msg)
	{
		$msgs = self::_Load($name);
		foreach ($msgs as $stored)
		{
			if ($stored == $msg)
			{
				return true;
			}
		}
		
		return false;
	}

	/**
	 * Добавляет сообщение.
	 * @param string $name
	 * @param string $msg
	 */
	public static function Add($name, $msg)
	{
		if (self::_IsStored($name, $msg) == false)
		{
			$msgs = self::_Load($name);
			$msgs[] = $msg;
			self::_Save($name, $msgs);
		}
	}
	
	/**
	 * Загружает список сообщение по имени.
	 * @param string $name
	 * @return array
	 */
	public static function Get($name)
	{
		$result = self::_Load($name);
		
		//Удаляем эту группу сообщений:
		self::_Save($name, null);
		
		return $result;
	}
};
