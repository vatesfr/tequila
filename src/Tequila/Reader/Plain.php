<?php

/**
 * This writer simply reads the data from the standard input.
 *
 * Due to the implementation, it is currently non testable.
 *
 * @codeCoverageIgnore
 */
class Tequila_Reader_Plain extends Tequila_Reader
{
	public function read(Tequila $tequila)
	{
		return fgets(STDIN);
	}
}
