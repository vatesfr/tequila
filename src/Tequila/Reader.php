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
	 */
	abstract public function read(Tequila $tequila, $prompt);

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
		/*
		 * Tequila_Reader_Readline is very limited:
		 * - it does not provide any completion;
		 * - it does not save the history between runs.
		 *
		 * It is therefore disabled for now and all advanced editing will be
		 * provided by “rlwrap”.
		 */
		/* if (extension_loaded('readline')) */
		/* { */
		/* 	return new Tequila_Reader_Readline(); */
		/* } */

		return new Tequila_Reader_Plain();
	}
}
