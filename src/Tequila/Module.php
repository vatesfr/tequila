<?php

/**
 * This is the base class for Tequila modules.
 *
 * @author Julien Fontanet <julien.fontanet@isonoe.net>
 */
class Tequila_Module
{
	public function __construct($tequila)
	{
		$this->_tequila = $tequila;
	}

	protected $_tequila;
}
