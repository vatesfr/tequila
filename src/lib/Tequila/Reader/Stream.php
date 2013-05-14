<?php

/**
 * This writer simply reads the data from the $stream stream.
 *
 * Due to the implementation, it is currently non testable.
 *
 * @author Julien Fontanet <julien.fontanet@isonoe.net>
 *
 * @codeCoverageIgnore
 */
class Tequila_Reader_Stream extends Tequila_Reader
{
	/**
	 * Constructs a new stream reader.
	 *
	 * @param resource $stream Stream from where to read data.
	 */
	public function __construct($stream)
	{
		$this->_stream = $stream;
	}

	/**
	 * @see parent::read()
	 */
	public function read(Tequila $tequila, $prompt)
	{
		$tequila->write($prompt);
		return fgets($this->_stream);
	}

	/**
	 * @var resource
	 */
	private $_stream;
}
