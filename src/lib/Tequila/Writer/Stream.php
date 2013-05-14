<?php

/**
 * This writer simply writes the data on the $standard stream if it is
 * not an error or on the $error stream otherwhise.
 *
 * Due to the implementation, it is currently non testable.
 *
 * @author Julien Fontanet <julien.fontanet@isonoe.net>
 * @codeCoverageIgnore
 */
final class Tequila_Writer_Stream extends Tequila_Writer
{
	/**
	 * Construct a new stream writer.
	 *
	 * @param resource $standard Stream where to write if this is not
	 *     an error.
	 * @param null|resource $error Stream where to write if this is an
	 *     error. If null, defaults to $standard.
	 */
	public function __construct($standard, $error = null)
	{
		$this->_standard = $standard;
		$this->_error    = $error ?: $standard;
	}

	/**
	 * @see parent::write()
	 */
	public function write($string, $error)
	{
		fwrite($error ? $this->_standard : $this->_error, $string);
	}

	/**
	 * @var resource
	 */
	private $_standard;

	/**
	 * @var resource
	 */
	private $_error;
}
