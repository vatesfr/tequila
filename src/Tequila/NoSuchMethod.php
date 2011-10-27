<?php

final class Tequila_NoSuchMethod extends Tequila_Exception
{
	public function __construct($class_name, $method_name)
	{
		parent::__construct('No such method: '.$class_name.'::'.$method_name);
	}
}