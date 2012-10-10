<?php

/**
 * This class loader relies on Gallic_Loader.
 *
 * @author Julien Fontanet <julien.fontanet@isonoe.net>
 */
class Tequila_ClassLoader_Gallic extends Tequila_ClassLoader
{
	public function __construct($dirs)
	{
		$this->_dirs = (array) $dirs;
	}

	public function addDirectory($dir)
	{
		$this->_dirs[] = $dir;
	}

	public function load($class_name)
	{
		return Gallic_Loader::loadClass($class_name, $this->_dirs);
	}

	private $_dirs;
}
