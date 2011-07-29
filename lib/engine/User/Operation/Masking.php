<?php

/**
 * Скрытие/отображение гейммастеров.
 * @package User_Operation
 * @author Mic, 2010
 */
class User_Operation_Masking extends User_Operation_Base
{
	/**
	 * Возвращает персонажей с их флажками маскировки.
	 * @return array
	 */
	public function GetCharacters()
	{
		$result = array();
		
		//Преобразовываем список персонажей в массив:
		foreach (Env::Get()->user->GetAccount()->GetCharacters()->GetAll() as $character)
		{
			$result[$character->GetGuid()] = array(
				'name' => $character->GetName(),
				'hidden' => false,
			);
		}
		
		//Подгружаем информацию о спрятанных персонажах:
		if (count($result))
		{
			$visible = Env::Get()->db->Get('game')->Query('
				SELECT guid
				FROM #site.site_operation_masking
				WHERE guid IN('.implode(',', array_keys($result)).')
			')->FetchColumn();
			
			foreach ($visible as $guid)
			{
				$result[$guid]['hidden'] = true;
			}
		}
		
		return $result;
	}
};
