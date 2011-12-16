<?php

/**
 * This writer simply reads the data from the standard input.
 *
 * Due to the implementation, it is currently non testable.
 *
 * @author Julien Fontanet <julien.fontanet@isonoe.net>
 *
 * @codeCoverageIgnore
 */
class Tequila_Reader_Plain extends Tequila_Reader
{
	public function read(Tequila $tequila)
	{
		$tequila->write($tequila->prompt);
		return fgets(STDIN);
	}
}
