<?php

/**
 * Версия обычного модификатора truncate, но с поддержкой юникода.
 * @param string $string
 * @param integer $length
 * @param string $etc
 * @param bool $break_words
 * @param bool $middle
 * @return string
 */
function smarty_modifier_dm_truncate($string, $length = 80, $etc = '&hellip;', $break_words = false, $middle = false)
{
    if ($length == 0)
    {
        return '';
    }
    else if (Util_String::GetLength($string) > $length)
    {
        $length -= min($length, Util_String::GetLength($etc));
        if ($break_words == false && $middle == false)
        {
            $string = preg_replace('#\s+?(\S+)?$#U', '', Util_String::GetSubstring($string, 0, $length + 1));
        }

        if ($middle == false)
        {
            return Util_String::GetSubstring($string, 0, $length).$etc;
        }
        else
        {
            return Util_String::GetSubstring($string, 0, $length / 2).$etc.Util_String::GetSubstring($string, -$length / 2);
        }
    }
    else
    {
        return $string;
    }
}
