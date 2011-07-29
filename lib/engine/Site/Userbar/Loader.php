<?php

/**
 * Загрузчик юзербаров из БД.
 * @author Mic, 2010
 */
class Site_Userbar_Loader
{
	const FONT_NAME									= 'frizqt.ttf';
	
	const FLAG_NO_GRAY_TEXT							= 0x01;
	const FLAG_GM									= 0x02;
	const FLAG_NO_ONLINE_STATUS						= 0x04;
	const FLAG_NO_LOGOUT_TIME						= 0x08;
	const FLAG_NO_LOGO								= 0x10;
	
	/**
	 * Набор параметров для отображения картинки.
	 * @var integer
	 */
	private static $_flags;
	
	/**
	 * Размеры юзербара.
	 * @var array
	 */
	private static $_size;
	
	/**
	 * Возвращает адрес юзербара.
	 * @param integer $guid
	 * @return string
	 */
	public static function GetUrl($guid)
	{
		return Env::Get()->request->GetAbsoluteUrl('/userbar/'.$guid.'/', true);
	}
	
	/**
	 * Возвращает максимальную позицию персонажа в топах либо null,
	 * если персонаж в топах не замечен.
	 * @param integer $guid
	 * @return integer
	 */
	private static function _GetCharacterPlaceInTop($guid)
	{
		$place = null;
		
		$tops = Site_Server_Top::Get();
		foreach ($tops as $top)
		{
			foreach ($top as $i => $character)
			{
				if ($character['guid'] == $guid && ($place === null || $i < $place))
				{
					$place = $i;
					break;
				}
			}
		}
		
		return $place;
	}

	/**
	 * Загружает информацию о персонаже из БД.
	 * @param integer $id
	 * @return array
	 */
	private static function _LoadCharacterInfo($id)
	{
		$db = Env::Get()->db->Get('game');
		$info = $db->Query('
			SELECT c.guid, c.name, c.race, c.class, c.online, c.level, c.gender, c.logout_time AS logout,
				g.name AS guild, gr.rname AS guildRank, (TRUE
					AND aa.gmlevel > 0
					AND sov.guid IS NOT NULL
				) AS is_gm, ut.title
			FROM characters AS c
				LEFT JOIN guild_member AS gm USING(guid)
				LEFT JOIN guild AS g USING(guildid)
				LEFT JOIN guild_rank AS gr ON(gr.guildid = gm.guildid AND gr.rid = gm.rank)
				INNER JOIN #realm.account AS a ON(a.id = c.account)
				LEFT JOIN #realm.account_access AS aa ON(aa.id = a.id)
				LEFT JOIN #site.site_operation_masking AS sov ON(sov.guid = c.guid)
				LEFT JOIN #site.site_userbar_titles AS ut ON(ut.guid = c.guid)
			WHERE c.guid = :guid
		', array(
			'guid' => array('d', $id)
		))->FetchRow();
		
		if (empty($info))
		{
			throw new Exception_Http_NotFound('персонаж не найден');
		}

		return $info;
	}
	
	/**
	 * Возвращает путь к служебному файлу.
	 * @param string $file
	 * @return string
	 */
	private static function _GetPathToStuff($file)
	{
		return STUFF_ROOT.'userbar/'.$file;
	}
	
	/**
	 * Генерирует палитру.
	 * @param resource $image
	 * @return array
	 */
	protected static function _GetPalette($image) {
		$palette = array();
		$colors = array(
			'red' => 0xFF5555,
			'green' => 0x00FF60,
			'blue' => 0x00A8FF,
			'white' => 0xFFFFFF,
			'yellow' => 0xFFFF00,
		);
		
		//Заполняем:
		foreach ($colors as $name => $value) {
			$palette[$name] = imagecolorallocate($image, $value >> 16, ($value >> 8) & 0xFF, $value & 0xFF);
		}
		
		return $palette;
	}
	
	/**
	 * Красиво форматирует время оффлайн.
	 * @param integer $seconds
	 * @return string
	 */
	private static function _FormatOfflineTime($seconds)
	{
		if ($seconds < 60)
		{
			$text = Util_String::GetNumber($seconds, array('секунда', 'секунды', 'секунд'));
		}
		else if ($seconds < 3540)
		{
			$text = Util_String::GetNumber(ceil($seconds / 60), array('минута', 'минуты', 'минут'));
		}
		else if ($seconds < 3600 * 23)
		{
			$text = Util_String::GetNumber(ceil($seconds / 3600), array('час', 'часа', 'часов'));
		}
		else
		{
			$text = Util_String::GetNumber(ceil($seconds / 3600 / 24), array('день', 'дня', 'дней'));
		}

		return $text;
	}
	
	/**
	 * Рисует текстовые данные.
	 * @param resource $image
	 * @param array $palette
	 * @param array $info
	 */
	protected static function _DrawStrings($image, $palette, $info) {
		if ($info['is_gm'] && (self::$_flags & self::FLAG_GM)) {
			//Персонаж - гейммастер:
			$nameColor = $palette['green'];
		} else {
			//Проверяем, к какой фракции относится персонаж:
			if (in_array($info['race'], User_Character_Race::GetAllianceFactions())) {
				$nameColor = $palette['blue'];
			} else {
				$nameColor = $palette['red'];
			}
		}
		
		if ($info['is_gm'] && (self::$_flags & self::FLAG_GM)) {
			$info['name'] = '[GM]'.$info['name'];
		}
	
		$font = self::_GetPathToStuff(self::FONT_NAME);
		imagettftext($image, 15, 0, 71, 25, $palette['white'], $font, str_pad($info['level'], 2, '0', STR_PAD_LEFT));
		imagettftext($image, 12, 0, 103, 18, $nameColor, $font, $info['name']);
		
		if ($info['title']) {
			imagettftext($image, 9, 0, 103, 31, $palette['yellow'], $font, $info['title']);
		} else if ($info['guild']) {
			imagettftext($image, 9, 0, 103, 31, $nameColor, $font, $info['guildRank'].' из <'.$info['guild'].'>');
		}
		
		//Выводим онлайн-оффлайн статус:
		$onlineColor = $info['online'] ? $palette['green'] : $palette['red'];
		if ((self::$_flags & self::FLAG_NO_ONLINE_STATUS) == false) {
			$text = $info['online'] ? 'онлайн' : 'оффлайн';
			
			$box = imagettfbbox(10, 0, $font, $text);
			imagettftext($image, 10, 0, 376 - $box[4], 17, $onlineColor, $font, $text);
		}
		
		//Выводим время оффлайна:
		if ((self::$_flags & self::FLAG_NO_LOGOUT_TIME) == false && $info['online'] == false) {
			$text = self::_FormatOfflineTime(time() - $info['logout']);
			
			$box = imagettfbbox(10, 0, $font, $text);
			imagettftext($image, 9, 0, 376 - $box[4], 30, $onlineColor, $font, $text);
		}
	}
	
	/**
	 * Выводит иконки.
	 * @param resource $image
	 * @param array $info
	 */
	private static function _DrawIcons($image, $info)
	{
		$icons = imagecreatefromgif(self::_GetPathToStuff('icons.gif'));
		
		//Раса:
		imagecopyresampled($image, $icons, 4, 4, ($info['race'] - 1) * 64, ($info['gender'] + 1) * 64, 30, 30, 64, 64);
		
		//Класс:
		imagecopyresampled($image, $icons, 35, 4, ($info['class'] - 1) * 64, 0, 30, 30, 64, 64);
		
		//Место в топах:
		$place = self::_GetCharacterPlaceInTop($info['guid']);
		if ($place !== null)
		{
			$offset = ($place < 4) ? $place * 64 : 320;
			imagecopyresampled($image, $icons, 69, 4, $offset, 256, 31, 31, 64, 64);
		}
	}
	
	/**
	 * Обесцвечивает картинку, если нужно.
	 * @param resource $image
	 * @param array $info
	 */
	private static function _ApplyGrayscale($image, $info)
	{
		if (time() - $info['logout'] > 3600 * 24 && (self::$_flags & self::FLAG_NO_GRAY_TEXT) == false)
		{
			imagecopymergegray($image, $image, 0, 0, 0, 0, self::$_size['width'], self::$_size['height'], 0);
		}
	}
	
	/**
	 * Генерирует картинку по предоставленным данным.
	 * @param array $info
	 * @return string
	 */
	private static function _GenerateImage($info)
	{
		$path = self::_GetPathToStuff('background.png');
		$image = imagecreatefrompng($path);
		
		$size = getimagesize($path);
		self::$_size = array(
			'width' => $size[0],
			'height' => $size[1],
		);

		$palette = self::_GetPalette($image);
		self::_DrawStrings($image, $palette, $info);
		self::_DrawIcons($image, $info);
		self::_ApplyGrayscale($image, $info);
		
		//Выводим картинку и по пути перехватываем:
		ob_start();
		imagepng($image);
		$result = ob_get_clean();
		
		return $result;
	}
	
	/**
	 * @param integer $id
	 * @param integer $flags
	 * @return string
	 */
	public static function Run($id, $flags)
	{
		self::$_flags = $flags;
		
		//Загружаем информацию про персонажа:
		$info = self::_LoadCharacterInfo($id);
		
		//Генерируем картинку:
		return self::_GenerateImage($info);
	}
};
