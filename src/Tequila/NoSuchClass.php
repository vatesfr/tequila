<?php

/**
 * @codeCoverageIgnore
 */
final class Tequila_NoSuchClass extends Tequila_Exception
{
	public function __construct($class_name)
	{
		parent::__construct('No such class: '.$class_name);
	}
}
