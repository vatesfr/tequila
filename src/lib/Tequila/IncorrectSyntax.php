<?php

/**
 * @author Julien Fontanet <julien.fontanet@isonoe.net>
 *
 * @codeCoverageIgnore
 */
class Tequila_IncorrectSyntax extends Tequila_Exception
{
	public $index;

	public function __construct($index, $reason)
	{
		$this->index = $index;

		parent::__construct($reason);
	}
}
