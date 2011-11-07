<?php

/**
 *
 */
abstract class Tequila_Logger
{
	/**
	 * Triggers the logging of a message of a given level.
	 *
	 * @param string  $message
	 * @param integer $level
	 */
	abstract protected function _log($message, $level);

	const
		DEBUG   = 1,
		NOTICE  = 2,
		WARNING = 4,
		FATAL   = 8;

	/**
	 * Returns the string representation of $level.
	 *
	 * @param integer $level
	 *
	 * @return string
	 */
	static public function getLevelName($level)
	{
		switch ($level)
		{
		case self::DEBUG:
			return 'DEBUG';
		case self::NOTICE:
			return 'NOTICE';
		case self::WARNING:
			return 'WARNING';
		case self::FATAL:
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
		$this->level = self::NOTICE | self::WARNING | self::FATAL;
	}

	/**
	 * Tells the logger there is something to log with a defined level.
	 *
	 * @param string  $message
	 * @param integer $level
	 */
	public final function log($message, $level = self::WARNING)
	{
		if (($level & $this->level) === $level)
		{
			$this->_log($message, $level);
		}
	}
}
