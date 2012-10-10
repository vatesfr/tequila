<?php

/**
 * @author Julien Fontanet <julien.fontanet@isonoe.net>
 *
 * @codeCoverageIgnore
 */
final class Tequila_UnspecifiedMethod extends Tequila_IncorrectSyntax
{
	/**
	 *
	 *
	 * @todo Removes $class_name, which is hard to compute during parsing
	 *     (i.e. it might be an nested command).
	 */
	public function __construct($class_name, $index)
	{
		parent::__construct($index, 'method not specified for class: '.$class_name);
	}
}
