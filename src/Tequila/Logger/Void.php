<?php

/**
 * This logger discard everything.
 *
 * This class does absolutly nothing, there is nothing to test.
 *
 * @codeCoverageIgnore
 */
class Tequila_Logger_Void extends Tequila_Logger
{
	protected function _log($message, $level)
	{}
}