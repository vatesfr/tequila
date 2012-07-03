<?php

/**
 * @author Julien Fontanet <julien.fontanet@isonoe.net>
 *
 * @codeCoverageIgnore
 */
class Tequila_UnspecifiedMethod extends Tequila_Exception
{
	/**
	 *
	 *
	 * @todo Removes $class_name, which is hard to compute during parsing
	 *     (i.e. it might be an nested command).
	 */
	public function __construct($class_name, $index)
	{
		// @todo Adds the index in the message.
		parent::__construct('Method not specified for class: '.$class_name);
	}
}
