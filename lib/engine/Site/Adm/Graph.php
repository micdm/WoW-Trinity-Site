<?php

/**
 * Построитель графиков для администратора.
 * @author Mic, 2010
 */
class Site_Adm_Graph
{
	/**
	 * График зависимости онлайна от времени.
	 * @var integer
	 */
	const TYPE_REGISTRATIONS_SIX_MONTHS				= 0;
	const TYPE_REGISTRATIONS_THREE_MONTHS			= 1;
	const TYPE_REGISTRATIONS_ONE_MONTH				= 2;
	const TYPE_ONLINE								= 3;
	
	/**
	 * Ширина графика.
	 * @var integer
	 */
	const IMAGE_WIDTH								= 500;
	
	/**
	 * Высота графика.
	 * @var integer
	 */
	const IMAGE_HEIGHT								= 400;
	
	/**
	 * Количество горизонтальных линий.
	 * @var integer
	 */
	const HORIZONTAL_GRID_LINES						= 5;
	
	/**
	 * Количество вертикальных линий.
	 * @var integer
	 */
	const VERTICAL_GRID_LINES						= 5;
	
	/**
	 * Интервал для генератора статистики про онлайн (в секундах).
	 * Три месяца = 7257600.
	 * @var integer
	 */
	const PERIOD_FOR_ONLINE_INFO					= 7257600;
	
	/**
	 * Генерируемая картинка. 
	 * @var resource
	 */
	private static $_image;
	
	/**
	 * Набор цветов.
	 * @var array
	 */
	private static $_palette;
	
	/**
	 * Данные, по которым будем рисовать график.
	 * @var array
	 */
	private static $_data;

	/**
	 * Минимальное значение по X.
	 * @var integer
	 */
	private static $_minX;
	
	/**
	 * Максимальное значение по X.
	 * @var integer
	 */
	private static $_maxX;
	
	/**
	 * Минимальное значение по Y.
	 * @var integer
	 */
	private static $_minY;
	
	/**
	 * Максимальное значение по Y.
	 * @var integer
	 */
	private static $_maxY;
	
	/**
	 * Генерирует палитру.
	 */
	private static function _GeneratePalette()
	{
		$colors = array(
			'green' => 0x00FF60,
			'white' => 0xFFFFFF,
			'lightgray' => 0x777777,
			'black' => 0x000000,
		);
		
		//Заполняем:
		$palette = array();
		foreach ($colors as $name => $value)
		{
			$palette[$name] = imagecolorallocate(self::$_image, $value >> 16, ($value >> 8) & 0xFF, $value & 0xFF);
		}
		
		self::$_palette = $palette;
	}
	
	/**
	 * Загружает статистику регистраций.
	 * @param integer $type
	 */
	private static function _LoadRegInfo($type)
	{
		//Выбираем количество месяцев:
		if ($type == self::TYPE_REGISTRATIONS_SIX_MONTHS)
		{
			$months = 6;
		}
		else if ($type == self::TYPE_REGISTRATIONS_THREE_MONTHS)
		{
			$months = 3;
		}
		else if ($type == self::TYPE_REGISTRATIONS_ONE_MONTH)
		{
			$months = 1;
		}
		
		self::$_data = Env::Get()->db->Get('game')->Query('
			SELECT
				TO_DAYS(joindate) AS days,
				UNIX_TIMESTAMP(DATE_FORMAT(joindate, \'%Y-%m-%d\')) AS time,
				COUNT(*) AS value
			FROM #realm.account
			WHERE joindate > DATE_SUB(NOW(), INTERVAL :months MONTH)
			GROUP BY days
			ORDER BY days
		', array(
			'months' => array('d', $months)
		))->FetchAll();
	}
	
	/**
	 * Загружает статистику онлайна.
	 */
	private static function _LoadOnlineInfo()
	{
		self::$_data = Env::Get()->db->Get('game')->Query('
			SELECT
				starttime AS time,
				maxplayers AS value
			FROM #realm.uptime
			WHERE TRUE
				AND realmid = 1
				AND maxplayers != 0
				AND starttime > UNIX_TIMESTAMP() - :period
			ORDER BY time
		', array(
			'period' => array('d', self::PERIOD_FOR_ONLINE_INFO)
		))->FetchAll();
	}
	
	/**
	 * Вычисляет граничные точки для оси X.
	 */
	private static function _CalculateXAxis()
	{
		$start = reset(self::$_data);
		self::$_minX = $start['time'];
		
		$end = end(self::$_data);
		self::$_maxX = $end['time'];
	}
	
	/**
	 * Вычисляет граничные точки для оси Y.
	 */
	private static function _CalculateYAxis()
	{
		self::$_minY = 0;
		
		self::$_maxY = 0;
		foreach (self::$_data as $point)
		{
			self::$_maxY = max(self::$_maxY, $point['value']);
		}
	}
	
	/**
	 * Возращает X-координату для значения.
	 * @param integer $value
	 * @return integer
	 */
	private static function _GetX($value)
	{
		return ($value - self::$_minX) * self::IMAGE_WIDTH / (self::$_maxX - self::$_minX);
	}
	
	/**
	 * Возвращает Y-координату для значения.
	 * @param integer $value
	 * @return integer
	 */
	private static function _GetY($value)
	{
		return (1 - ($value / self::$_maxY)) * self::IMAGE_HEIGHT;
	}
	
	/**
	 * Рисует сам график.
	 */
	private static function _DrawGraph()
	{
		//Картинка:
		self::$_image = imagecreatetruecolor(self::IMAGE_WIDTH, self::IMAGE_HEIGHT);
		
		//Палитра:
		self::_GeneratePalette();
		
		//Фон будет белый:
		imagefill(self::$_image, 0, 0, self::$_palette['white']);
		
		$prevX = 0;
		$prevY = self::IMAGE_HEIGHT;
		foreach (self::$_data as $point)
		{
			//Вычисляем координаты новой точки:
			$newX = self::_GetX($point['time']);
			$newY = self::_GetY($point['value']);
			
			imageline(self::$_image, $prevX, $prevY, $newX, $newY, self::$_palette['black']);
			
			$prevX = $newX;
			$prevY = $newY;
		}
		
		//Заливаем полученную фигуру:
		imagefill(self::$_image, 1, self::IMAGE_HEIGHT - 1, self::$_palette['lightgray']);
	}
	
	/**
	 * Рисует горизонтальную сетку.
	 */
	private static function _DrawHorizontalGrid()
	{
		for ($i = 0; $i <= self::HORIZONTAL_GRID_LINES; $i += 1)
		{
			$value = self::$_minY + floor((self::$_maxY - self::$_minY) * $i / self::HORIZONTAL_GRID_LINES);
			$y = self::_GetY($value);
			
			//Линия:
			imageline(self::$_image, 0, $y, self::IMAGE_WIDTH, $y, self::$_palette['green']);
			
			//Число:
			imagestring(self::$_image, 2, 3, $y, $value, self::$_palette['green']);
		}
	}
	
	/**
	 * Рисует вертикальную сетку.
	 */
	private static function _DrawVerticalGrid()
	{
		for ($i = 0; $i <= self::VERTICAL_GRID_LINES; $i += 1)
		{
			$value = self::$_minX + floor((self::$_maxX - self::$_minX) * $i / self::VERTICAL_GRID_LINES);
			$x = self::_GetX($value);

			//Линия:
			imageline(self::$_image, $x, 0, $x, self::IMAGE_HEIGHT, self::$_palette['green']);
			
			//Число:
			imagestring(self::$_image, 2, $x + 3, self::IMAGE_HEIGHT - 15, date('d M', $value), self::$_palette['green']);
		}
	}
	
	/**
	 * Возвращает содержимое картинки-графика.
	 * @param integer $type
	 * @return string
	 */
	public static function Run($type)
	{
		//Загружаем нужную информацию:
		if ($type == self::TYPE_REGISTRATIONS_SIX_MONTHS || $type == self::TYPE_REGISTRATIONS_THREE_MONTHS || $type == self::TYPE_REGISTRATIONS_ONE_MONTH)
		{
			self::_LoadRegInfo($type);
		}
		else if ($type == self::TYPE_ONLINE)
		{
			self::_LoadOnlineInfo();
		}
		else
		{
			throw new Exception_Http_NotFound('неизвестный тип графиков '.$type);
		}
		
		//Строим график:
		self::_CalculateXAxis();
		self::_CalculateYAxis();
		self::_DrawGraph();
		self::_DrawHorizontalGrid();
		self::_DrawVerticalGrid();
		
		//Результат:
		ob_start();
		imagepng(self::$_image);
		return ob_get_clean();
	}
};
