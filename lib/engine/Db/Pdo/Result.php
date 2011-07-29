<?php

class Db_Pdo_Result
{
	/**
	 * 
	 * @var PDOStatement
	 */
	private $_statement;
	
	public static function Factory(PDOStatement $statement)
	{
		return new self($statement);
	}
	
	public function __construct(PDOStatement $statement)
	{
		$statement->setFetchMode(PDO::FETCH_ASSOC);
		$this->_statement = $statement;
	}
	
	/**
	 * Выбирает первый ряд.
	 * @return array
	 */
	public function FetchRow()
	{
		return $this->_statement->fetch();
	}
	
	/**
	 * Выбирает все данные.
	 * @return array
	 */
	public function FetchAll()
	{
		return $this->_statement->fetchAll();
	}
	
	/**
	 * Выбирает первую ячейку.
	 * @return string
	 */
	public function FetchOne()
	{
		return $this->_statement->fetchColumn();
	}
	
	/**
	 * Выбирает первую колонку.
	 * @return array
	 */
	public function FetchColumn()
	{
		$result = array();
		while ($column = $this->_statement->fetchColumn())
		{
			$result[] = $column;
		}
		
		return $result;
	}
	
	/**
	 * Возвращает количество рядов.
	 * @return integer
	 */
	public function GetRowsCount()
	{
		return $this->_statement->rowCount();
	}
};
