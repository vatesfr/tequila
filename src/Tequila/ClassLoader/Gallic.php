<?php

/**
 * This class loader relies on Gallic_Loader.
 */
class Tequila_ClassLoader_Gallic extends Tequila_ClassLoader
{
	public $dirs = array();

	public function load($class_name)
	{
		return Gallic_Loader::loadClass($class_name, $this->dirs);
	}
}