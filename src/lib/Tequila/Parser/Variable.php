<?php

/**
 * This class is used to store nested commands.
 *
 * @codeCoverageIgnore Trivial code.
 */
final class Tequila_Parser_Variable extends Tequila_Parser_Node
{
	public $name;

	public function __construct($name)
	{
		$this->name = $name;
	}
}
