<?php

/**
 * This logger discard everything.
 */
class Tequila_Logger_Void extends Tequila_Logger
{
	protected function _log($message, $level)
	{}
}