<?php

/**
 * @author Julien Fontanet <julien.fontanet@isonoe.net>
 *
 * @codeCoverageIgnore
 */
final class Tequila_IncorrectSyntax extends Tequila_Exception
{
	public function __construct($index)
	{
		parent::__construct('incorrect syntax at character '.$index);
	}
}
