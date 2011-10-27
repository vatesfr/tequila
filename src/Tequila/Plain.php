<?php

/**
 * This version of Tequila does not provide any advanced typing features.
 */
class Tequila_Plain extends Tequila
{
	public function __construct()
	{
		parent::__construct();
	}

	public function __get($name)
	{
		switch ($name)
		{
		case 'history':
			$name = '_'.$name;
			return $this->$name;
		}

		return parent::__get($name);
	}

	public function addToHistory($string)
	{
		$this->_history[] = $string;
	}

	public function clearHistory()
	{
		$this->_history = array();
	}

	public function prompt($prompt = '')
	{
		$this->write($prompt);

		return fgets(STDIN);
	}

	private $_history = array();
}
