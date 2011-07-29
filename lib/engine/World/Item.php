<?php

/**
 * Игровой предмет.
 * @package World
 * @author Mic, 2010
 */
class World_Item extends ArrayType
{
	/**
	 * Информация о предмете.
	 * @var array
	 */
	protected $_info;
	
	/**
	 * @param mixed $ids
	 * @return World_Item
	 */
	public static function Factory($ids)
	{
		if (empty($ids))
		{
			return null;
		}
		
		//Преобразовываем в числа и убираем дубли:
		$ids = array_map('intval', (array)$ids);
		
		//Загружаем:
		$data = Env::Get()->db->Get('game')->Query('
			SELECT
				*,
				it.entry,
				COALESCE(li.name_loc8, it.name) AS name,
				sdg.price
			FROM #world.item_template AS it
				LEFT JOIN #site.site_donate_goods AS sdg ON(sdg.entry = it.entry)
				LEFT JOIN #world.locales_item AS li ON(li.entry = it.entry)
			WHERE it.entry IN('.implode(',', $ids).')
		')->FetchAll();

		$result = array();
		foreach ($ids as $i => $id)
		{
			$item = null;
			
			//Находим в результат предмет по идентификатору:
			foreach ($data as $row)
			{
				if ($row['entry'] == $id)
				{
					$item = new self($row);
					break;
				}
			}

			$result[$i] = $item;
		}

		return $result;
	}
	
	/**
	 * @param array $data
	 */
	public function __construct($data)
	{
		$this->_info = $data;
	}
	
	/**
	 * Возвращает идентификатор предмета.
	 * @return integer
	 */
	public function GetEntry()
	{
		return $this->_info['entry'];
	}
	
	/**
	 * Возвращает название предмета.
	 * @return string
	 */
	public function GetName()
	{
		return $this->_info['name'];
	}
	
	/**
	 * Возвращает класс предмета.
	 * @return integer
	 */
	public function GetClass()
	{
		return $this->_info['class'];
	}
	
	/**
	 * Возвращает подкласс предмета.
	 * @return integer
	 */
	public function GetSubclass()
	{
		return $this->_info['subclass'];
	}
	
	/**
	 * Возвращает цену предмета в чеках.
	 * @return float
	 */
	public function GetPrice()
	{
		return $this->_info['price'];
	}
	
	/**
	 * Доступно ли для покупки через donate-систему?
	 * Возвращает null, если предмет вообще не находится в donate-системе.
	 * @return boolean
	 */
	public function IsAvailableForDonate()
	{
		return isset($this->_info['is_active']) ? (bool)$this->_info['is_active'] : null;
	}
	
	/**
	 * Возвращает название иконки.
	 * @return string
	 */
	public function GetIcon()
	{
		$info = Third_Aowow::GetItem($this->GetEntry());
		$image = $info['iconname'];
		if (empty($image))
		{
			$image = 'inv_misc_questionmark';
		}
		
		return Third_Aowow::GetImagePath($image, 'medium');
	}
	
	/**
	 * Возвращает текст всплывающей подсказки.
	 * @return string
	 */
	public function GetDescription()
	{
		$info = Third_Aowow::GetItem($this->GetEntry());
		$info = $info['info'];
		
		//Убираем ссылки на aowow:
		$info = preg_replace('#<a[^>]*?>(.+?)</a>#', '<a href="#">$1</a>', $info);
		
		return $info;
	}
};
