<?php

/**
 *
 */
abstract class Tequila_Logger
{
	const
		DEBUG   = 1,
		NOTICE  = 2,
		WARNING = 4,
		FATAL   = 8;

	/**
	 * Returns the string representation of $level.
	 */
	static public function getLevelName($level)
	{
		switch ($level)
		{
		case DEBUG:
			return 'DEBUG';
		case NOTICE:
			return 'NOTICE';
		case WARNING:
			return 'WARNING';
		case FATAL:
			return 'FATAL';
		}

		return '';
	}

	/**
	 * This entry defines the verbosity level of the logger.
	 */
	public
		$level;

	public function __construct()
	{
		$this->level = NOTICE | WARNING | FATAL;
	}

	/**
	 * Tells the logger there is something to log with a defined level.
	 */
	public final function log($message, $level = WARNING)
	{
		if (($level & $this->level) !== 0)
		{
			$this->_log($message, $level);
		}
	}

	/**
	 * Triggers the logging of a message of a given level.
	 */
	protected abstract function _log($message, $level);
}
