<?php

/**
 * Фильтр убирает все лишние пробелы между тегами из финального исходного кода.
 * @param string $source
 * @return string
 */
function smarty_postfilter_dm_remove_spaces(&$source)
{
	return preg_replace('#\s{2,}#', '', $source);
}
