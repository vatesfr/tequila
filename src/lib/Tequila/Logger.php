<?php

/**
 * @author Julien Fontanet <julien.fontanet@isonoe.net>
 *
 * @codeCoverageIgnore
 */
abstract class Tequila_Logger
{
	/**
	 * Triggers the logging of a message of a given level.
	 *
	 * @param string $message Message to log.
	 * @param integer $level Log level of this message.
	 */
	abstract protected function _log($message, $level);

	/**
	 * These are the criticy levels that can be associated to a
	 * message.
	 *
	 * @var integer
	 */
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
		static $map = null;

		if ($map === null)
		{
			$class = new ReflectionClass(__CLASS__);
			$map = array_flip($class->getConstants());
		}

		return (isset($map[$level]) ? $map[$level] : '[unknown]');
	}

	/**
	 * This entry defines the verbosity level of the logger.
	 *
	 * @param integer
	 */
	public $level;

	/**
	 * Constructs a logger with the default logging level (every
	 * messages but debug).
	 */
	public function __construct()
	{
		$this->level = self::NOTICE | self::WARNING | self::FATAL;
	}

	/**
	 * Logs a message if its criticity level is present in this logger
	 * level.
	 *
	 * @param string $message Message to log.
	 * @param integer $level Log level of this message.
	 */
	public final function log($message, $level = self::WARNING)
	{
		if (($level & $this->level) === $level)
		{
			$this->_log($message, $level);
		}
	}
}
