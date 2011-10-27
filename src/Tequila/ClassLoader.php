<?php

/**
 *
 */
abstract class Tequila_ClassLoader
{
	/**
	 * Loads a class.
	 *
	 * @param string $class_name The name of the class.
	 *
	 * @return boolean Whether the class has been correctly loaded.
	 */
	public abstract function load($class_name);
}
