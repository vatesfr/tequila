<?php

/**
 * This is the base class for every exceptions raised by Tequila.
 */
class Tequila_Exception extends Exception
{
	public function __construct($message)
	{
		parent::__construct($message);
	}
}
