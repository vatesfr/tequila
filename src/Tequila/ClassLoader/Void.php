<?php

/**
 * This class loader does not load anything.
 */
class Tequila_ClassLoader_Void extends Tequila_ClassLoader
{
	public function load($class_name)
	{
		return false;
	}
}