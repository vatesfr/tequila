<?php

/**
 * @codeCoverageIgnore
 */
class Tequila_UnspecifiedMethod extends Tequila_Exception
{
	public function __construct($class_name)
	{
		parent::__construct('Method not specified for class: '.$class_name);
	}
}
