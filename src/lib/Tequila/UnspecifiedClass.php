<?php

/**
 * @author Julien Fontanet <julien.fontanet@isonoe.net>
 *
 * @codeCoverageIgnore
 */
final class Tequila_UnspecifiedClass extends Tequila_IncorrectSyntax
{
	public function __construct($index)
	{
		parent::__construct($index, 'class not specified');
	}
}
