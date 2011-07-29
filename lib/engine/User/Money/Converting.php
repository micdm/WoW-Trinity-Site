<?php

/**
 * Конвертирование различных материальных средств во внутреннюю валюту.
 * @author Mic, 2010
 * @package User_Money
 */
class User_Money_Converting
{
	/**
	 * Количество чеков за один SMS-доллар.
	 * @var float
	 */
	const CHEQUES_FOR_SMS_DOLLAR					= 1.0;
	
	/**
	 * Количество чеков за один MMOTOP-голос.
	 * @var float
	 */
	const CHEQUES_FOR_MMOTOP_VOTE					= 0.5;
	
	/**
	 * Количество чеков за один WMR.
	 * @var float
	 */
	const CHEQUES_FOR_WMR							= 0.05;
	
	/**
	 * Количество золота за один чек.
	 * @var float
	 */
	const GOLD_PER_CHEQUE							= 200000;
	
	/**
	 * Конвертирует SMS-цену в чеки.
	 * @param float $input
	 * @return float
	 */
	public static function FromSms($input)
	{
		return self::CHEQUES_FOR_SMS_DOLLAR * $input;
	}
	
	/**
	 * Конвертирует MMOTOP-голоса в чеки.
	 * @param float $input
	 * @return float
	 */
	public static function FromMmotop($input)
	{
		return self::CHEQUES_FOR_MMOTOP_VOTE * $input;
	}
	
	/**
	 * Конвертирует WMR в чеки.
	 * @param float $input
	 * @return float
	 */
	public static function FromWmr($input)
	{
		return self::CHEQUES_FOR_WMR * $input;
	}
	
	/**
	 * Конвертирует чеки в WMR.
	 * @param float $cheques
	 * @return float
	 */
	public static function ToWmr($cheques)
	{
		return $cheques / self::CHEQUES_FOR_WMR;
	}
	
	/**
	 * Конвертирует чеки в золото.
	 * @param float $input
	 * @return float
	 */
	public static function ToGold($input)
	{
		return self::GOLD_PER_CHEQUE * $input;
	}
};
