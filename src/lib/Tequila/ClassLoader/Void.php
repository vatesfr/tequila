<?php

/**
 * This class loader does not load anything.
 *
 * This class does absolutly nothing, there is nothing to test.
 *
 * @author Julien Fontanet <julien.fontanet@isonoe.net>
 *
 * @codeCoverageIgnore
 */
class Tequila_ClassLoader_Void extends Tequila_ClassLoader
{
	public function load($class_name)
	{
		return false;
	}
}
