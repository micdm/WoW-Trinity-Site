#!/usr/bin/php
<?php

/**
 * Создает необходимые директории.
 */
function CreateDirectories()
{
	foreach (array('large', 'medium', 'small', 'tiny') as $name)
	{
		$name = OUTPUT_DIR.'/'.$name;
			if (file_exists($name) == false)
		{
			mkdir($name);
		}
	}
}

/**
 * Конвертирует одну иконку.
 * @param string $source
 * @param string $destination
 * @param integer $size
 */
function ConvertImage($source, $destination, $size)
{
	if (getimagesize($source) == false)
	{
		print('can not convert '.$source."\n");
		return;
	}
	
	$from = imagecreatefrompng($source);
	$to = imagecreatetruecolor($size, $size);
	
	imagecopyresized($to, $from, 0, 0, 0, 0, $size, $size, 64, 64);
	
	$name = basename($source);
	imagejpeg($to, $destination.substr($name, 0, strpos($name, '.')).'.jpg');
}

/**
 * Конвертирует все файлы в исходной директории.
 */
function ConvertFiles()
{
	$sizes = array(
		'large' => 64,
		'medium' => 36,
		'small' => 18,
		'tiny' => 15,
	);
	
	$dir = dir(INPUT_DIR);
	while (($file = $dir->read()) !== false)
	{
		if ($file == '.' || $file == '..')
		{
			continue;
		}

		foreach ($sizes as $name => $size)
		{
			ConvertImage(INPUT_DIR.'/'.$file, OUTPUT_DIR.'/'.$name.'/', $size);
		}
	}
	
	$dir->close();
}

//Проверяем параметры:
if (empty($_SERVER['argv'][1]))
{
	exit("you should specify input directory\n");
}
else if (empty($_SERVER['argv'][2]))
{
	exit("you should specify output directory\n");
}

define('INPUT_DIR', $_SERVER['argv'][1]);
define('OUTPUT_DIR', $_SERVER['argv'][2]);

CreateDirectories();
ConvertFiles();
