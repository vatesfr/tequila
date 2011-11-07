<?php

/**
 *
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