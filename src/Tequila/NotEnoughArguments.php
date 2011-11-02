<?php

final class Tequila_NotEnoughArguments extends Tequila_Exception
{
	public function __construct($class_name, $method_name, $n)
	{
		parent::__construct(
			$class_name.'::'.$method_name.' expects at least '.$n.' arguments'
		);
	}
}
