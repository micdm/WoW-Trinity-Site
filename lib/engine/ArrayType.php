<?php

/**
 * Реализация доступа как к массиву.
 * @author Mic, 2010
 */
class ArrayType implements ArrayAccess
{
	/**
	 * Преобразовывает ключ к названию метода.
	 * @param string $key
	 * @return string
	 */
	private function _GetMethodName($key)
	{
		//Методы Is* вызываем как есть, остальным добавляем префикс Get*:
		return strpos($key, 'is') === 0 ? $key : 'Get'.$key;
	}
	
	public function offsetExists($key)
	{
		return method_exists($this, $this->_GetMethodName($key));
	}
	
	public function offsetGet($key)
	{
		return call_user_func(array($this, $this->_GetMethodName($key)));
	}
	
	public function offsetSet($key, $value)
	{
		throw new Exception_Runtime('нельзя присвоить значение');
	}
	
	public function offsetUnset($key)
	{
		throw new Exception_Runtime('нельзя удалить элемент');
	}
};
