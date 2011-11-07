<?php

/**
 *
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
}