<?php

/**
 * This class is used to store nested commands.
 */
final class Tequila_Parser_Command extends Tequila_Parser_Node
{
	public $class;

	public $method;

	public $args;

	public function __construct($class, $method, array $args)
	{
		$this->class  = $class;
		$this->method = $method;
		$this->args   = $args;
	}
}
