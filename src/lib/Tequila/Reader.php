<?php

/**
 * A reader  is an object  which has for  sole mission to read  strings (usually
 * lines) from somewhere.
 *
 * This class provides no implementations, there is nothing to test.
 *
 * @author Julien Fontanet <julien.fontanet@isonoe.net>
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
	 * @param string   $prompt  This string  is the prompt associated  with this
	 *                          reading.
	 *
	 * @return string|false The string read or false if an error occured.
	 */
	abstract public function read(Tequila $tequila, $prompt);
}
