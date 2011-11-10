<?php

/**
 * A reader  is an object  which has for  sole mission to read  strings (usually
 * lines) from somewhere.
 *
 * This class provides no implementations, there is nothing to test.
 *
 * @codeCoverageIgnore
 */
abstract class Tequila_Reader
{
	/**
	 * Reads a string (usually a single line).
	 *
	 * @param Tequila  $tequila The Tequila instance which  requires the reading
	 *                          (for advanced feature such as completion).
	 */
	abstract public function read(Tequila $tequila);

	/**
	 * Creates  a Tequila_Reader  depending  whether the  Readline extension  is
	 * loaded or not.
	 *
	 * Because  this code  is platform  dependent, the  code coverage  cannot be
	 * complete, to avoid this problem we deliberately ignore it.
	 *
	 * @codeCoverageIgnore
	 */
	public static function factory()
	{
		if (extension_loaded('readline'))
		{
			return new Tequila_Reader_Readline();
		}

		return new Tequila_Reader_Plain();
	}
}
