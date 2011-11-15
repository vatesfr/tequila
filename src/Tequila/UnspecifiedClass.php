<?php

/**
 * @author Julien Fontanet <julien.fontanet@isonoe.net>
 *
 * @codeCoverageIgnore
 */
class Tequila_UnspecifiedClass extends Tequila_Exception
{
	public function __construct()
	{
		parent::__construct('Class not specified');
	}
}
