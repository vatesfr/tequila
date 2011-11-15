<?php

/**
 * This logger discard everything.
 *
 * This class does absolutly nothing, there is nothing to test.
 *
 * @author Julien Fontanet <julien.fontanet@isonoe.net>
 *
 * @codeCoverageIgnore
 */
class Tequila_Logger_Void extends Tequila_Logger
{
	protected function _log($message, $level)
	{}
}