<?php

/**
 * A writer  is an object which  has for sole  mission to write the  passed data
 * somewhere.
 *
 * This class provides no implementations, there is nothing to test.
 *
 * @codeCoverageIgnore
 */
abstract class Tequila_Writer
{
	/**
	 * Writes a string.
	 *
	 * @param string $string The string.
	 * @param boolean $error Whether it is an error message.
	 */
	abstract public function write($string, $error);
}