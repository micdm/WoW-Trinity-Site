<?php

/**
 * Обычный перенос персонажа.
 * @package User_Operation_Action_Transfer
 * @author Mic, 2010
 */
class User_Operation_Action_Transfer_Main extends User_Operation_Action_Base
{
	protected function _GetSubjectForMailConfirm()
	{
		return 'Перенос';
	}

	protected function _Setup()
	{
		$this
			->_SetMailConfirmRequired()
			->_AddCharacter('guid', array('mustBelong' => true))
			->_AddAccount('account', array(
				'isName' => true,
				'mustDiffer' => true,
			));
	}
	
	public function GetDescription($accounts, $characters, $plain, $custom)
	{
		$character = $characters['guid'];
		$account = $accounts['account'];
		return sprintf('Перенос персонажа %s на аккаунт %d', $character['name'], $account);
	}
	
	protected function _CheckAdditionalConditions()
	{
		//Перенос на премиум-аккаунты не разрешен:
		if ($this->GetAccount()->IsPremium())
		{
			throw new Exception_User_Operation_BadCondition('Вам нужно подождать, пока указанный аккаунт перестанет получать повышенный опыт');
		}
		
		//Проверяем количество персонажей на втором аккаунте:
		$max = Env::Get()->config->Get('charactersOnAccountMaxCount');
		if (count($this->GetAccount()->GetCharacters()) >= $max)
		{
			throw new Exception_User_Operation_BadCondition('на указанном аккаунте слишком много персонажей (максимум '.$max.')');
		}
		
		//Проверяем исходный аккаунт после переноса:
		$list = $this->_GetCharacterList($this->GetCharacter()->GetAccount(), null, $this->GetCharacter());
		try
		{
			$this->_CheckListForAnyErrors($list);
		}
		catch (Exception_User_Operation_Transfer_BadFaction $e)
		{
			//На исходном аккаунте могут быть разные фракции - мало ли что.
		}
		catch (Exception_User_Operation_Transfer_HighLevelRequiredForDk $e)
		{
			throw new Exception_User_Operation_BadCondition('Вы не можете перенести персонажа, если на Вашем аккаунте есть рыцарь смерти, но не остается других высокоуровневых персонажей');
		}
		
		//Проверяем целевой аккаунт после переноса:
		$list = $this->_GetCharacterList($this->GetAccount(), $this->GetCharacter(), null);
		try
		{
			$this->_CheckListForAnyErrors($list);
		}
		catch (Exception_User_Operation_Transfer_BadFaction $e)
		{
			throw new Exception_User_Operation_BadCondition('на указанном аккаунте есть персонажи противоположной фракции');
		}
		catch (Exception_User_Operation_Transfer_HighLevelRequiredForDk $e)
		{
			$levelForDk = Env::Get()->config->Get('minLevelForDeathknight');
			throw new Exception_User_Operation_BadCondition('Вы можете перенести рыцаря смерти, только если на целевом аккаунте есть персонажи высокого уровня (выше '.$levelForDk.')');
		}
	}

	protected function _DoSomeActions()
	{
		$character = $this->GetCharacter();
		
		//Меняем аккаунт:
		$character->SetAccount($this->GetAccount());
		
		//Помечаем как перенесенного:
		$this->_AddToCompleteTransfers($character);
		
		$this->_SetSuccessMessages('персонаж перенесен');
	}
	
	/**
	 * Возвращает список персонажей на аккаунте. По пути удаляет и добавляет новых.
	 * @param User_Account $account
	 * @param User_Character $include
	 * @param User_Character $exclude
	 * @return array
	 */
	protected function _GetCharacterList($account, $include, $exclude)
	{
		$result = array();
		foreach ($account->GetCharacters()->GetAll() as $character)
		{
			if (empty($exclude) || $character->GetGuid() != $exclude->GetGuid())
			{
				$result[] = $character;
			}
		}
		
		if ($include)
		{
			$result[] = $include;
		}

		return $result;
	}
	
	/**
	 * Проверяет список на наличие персонажей разных фракций.
	 * @param array $list
	 */
	protected function _CheckFactions($list)
	{
		//Если возможно держать на аккаунте разные фракции, выходим:
		if (Env::Get()->config->Get('canHaveBothFactions'))
		{
			return;
		}
		
		if (count($list))
		{
			//Фракцию первого принимаем за эталонную:
			$isAlliance = $list[0]->GetRace()->IsAlliance();
			
			//Проверяем остальных:
			foreach ($list as $character)
			{
				if ($character->GetRace()->IsAlliance() != $isAlliance)
				{
					throw new Exception_User_Operation_Transfer_BadFaction();
				}
			}
		}
	}
	
	/**
	 * Проверяет наличие в списке персонажа достаточного высокого уровня
	 * для создания рыцаря смерти.
	 * @param array $list
	 */
	protected function _CheckHighLevelForDk($list)
	{
		$maxLevel = 0;
		$hasDk = false;
		foreach ($list as $character)
		{
			//Ищем рыцаря смерти:
			if ($character->GetClass()->IsDeathknight())
			{
				$hasDk = true;
			}
			else
			{
				//Вычисляем максимальный уровень среди не-DK:
				$maxLevel = max($maxLevel, $character->GetLevel());
			}
		}
		
		//Если рыцарь смерти существует, то должен быть и высокоуровневый персонаж:
		if ($hasDk && $maxLevel < Env::Get()->config->Get('minLevelForDeathknight'))
		{
			throw new Exception_User_Operation_Transfer_HighLevelRequiredForDk();
		}
	}
	
	/**
	 * Проверяет список персонажей на возможные ошибки.
	 * @param array $list
	 */
	protected function _CheckListForAnyErrors($list)
	{
		$this->_CheckFactions($list);
		$this->_CheckHighLevelForDk($list);
	}
	
	/**
	 * Добавляет персонажа в список завершенных переносов.
	 * @param User_Character $character
	 */
	protected function _AddToCompleteTransfers($character)
	{
		Env::Get()->db->Get('game')->Query('
			INSERT IGNORE INTO #site.site_operation_transfer_complete (guid)
			VALUES (:guid)
		', array(
			'guid' => array('d', $character->GetGuid())
		));
	}
};
