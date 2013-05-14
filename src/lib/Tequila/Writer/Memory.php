<?php

/**
 * This writer simply writes the data on the writers output if $error is false,
 * or on the writers error otherwise.
 *
 * Due to the implementation, it is currently non testable.
 *
 * @author Julien Fontanet <julien.fontanet@isonoe.net>
 *
 * @codeCoverageIgnore
 */
final class Tequila_Writer_Memory extends Tequila_Writer
{
	/**
	 * @see parent::write()
	 */
	public function write($string, $error)
	{
		$this->_data .= $string;
	}

	/**
	 * Returns the content of the buffer and clears it.
	 *
	 * @return string The content of the buffer.
	 */
	public function pop()
	{
		$data = $this->_data;
		$this->_data = '';

		return $data;
	}

	/**
	 * @var string
	 */
	private $_data = '';
}
