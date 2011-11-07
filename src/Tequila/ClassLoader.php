<?php

/**
 * A class loader is an object which has for sole mission to load classes.
 *
 * This class provides no implementations, there is nothing to test.
 *
 * @codeCoverageIgnore
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
