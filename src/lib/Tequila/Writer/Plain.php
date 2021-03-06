<?php

/**
 * This writer simply writes the data on the standard output if $error is false,
 * or on the standard error otherwise.
 *
 * Due to the implementation, it is currently non testable.
 *
 * @author Julien Fontanet <julien.fontanet@isonoe.net>
 *
 * @codeCoverageIgnore
 */
final class Tequila_Writer_Plain extends Tequila_Writer
{
	/**
	 * @see parent::write()
	 */
	public function write($string, $error)
	{
		fwrite($error ? STDERR : STDOUT, $string);
	}
}
