<?php

/**
 * Рисовальщик юзербаров.
 * @author Mic, 2010
 */
class Site_Userbar_View extends View
{
	public function Index($args)
	{
		//Загружаем картинку:
		$data = Site_Userbar_Loader::Run($args['id'], intval($args['flags']));
		
		//Отдаем:
		Env::Get()->response->SetContentType('image/png');
		return $data;
	}
};
