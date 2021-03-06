<?php

/**
 * This logger stores the data in a file.
 *
 * @author Julien Fontanet <julien.fontanet@isonoe.net>
 *
 * @property string $file
 */
class Tequila_Logger_File extends Tequila_Logger
{
	public function __construct($file)
	{
		parent::__construct();

		$this->file = $file;
	}

	public function __get($name)
	{
		switch ($name)
		{
		case 'file':
			$name = '_'.$name;
			return $this->$name;
		default:
			throw new Exception('Getting incorrect property: '.__CLASS__.'::'.
			                    $name);
		}
	}

	public function __destruct()
	{
		$this->_closeFile();
	}

	public function __set($name, $value)
	{
		switch ($name)
		{
		case 'file':
			$this->_closeFile();

			$handle = fopen($value, 'a');
			if ($handle === false)
			{
				throw new Exception('Failed to open: '.$value);
			}

			$this->_file = $value;
			$this->_handle = $handle;

			break;
		default:
			throw new Exception('Setting incorrect property: '.__CLASS__.'::'.
			                    $name);
		}
	}

	protected function _log($message, $level)
	{
		if ($this->_handle === null)
		{
			throw new Exception('No file to write');
		}

		if (fwrite($this->_handle, $message) === false)
		{
			throw new Exception('Failed to write: '.$this->_file);
		}
	}

	private
		$_file = '',
		$_handle;

	private function _closeFile()
	{
		if ($this->_handle === null)
		{
			return;
		}

		if (fclose($this->_handle) === false)
		{
			throw new Exception('Failed to close: '.$this->_file);
		}

		$this->_file = '';
		$this->_handle = null;
	}
}
