<?php

/**
 * This is Tequila's main class (obviously).
 *
 * @property Tequila_ClassLoader $class_loader
 * @property Tequila_Logger      $logger

 * @property-read array   $history
 * @property-read boolean $is_running
 * @property-read string  $user
 */
abstract class Tequila
{
	/**
	 * Creates  a Tequila  instance  depending whether  the  Readline module  is
	 * loaded or not.
	 *
	 * @return Tequila
	 */
	public static function create()
	{
		if (extension_loaded('readline'))
		{
			return new Tequila_Readline();
		}

		return new Tequila_Plain();
	}

	// If we want to set these properties private, we should use the “__get” and
	// “__set” magic methods to keep API compatibility.
	public
		$prompt = 'tequila> ';

	/**
	 * @todo Unit tests.
	 */
	public function __get($name)
	{
		switch ($name)
		{
		case 'class_loader':
		case 'is_running':
		case 'logger':
		case 'user':
			$name = '_'.$name;
			return $this->$name;
		}

		throw new Tequila_Exception(
			'Getting incorrect property: '.__CLASS__.'::'.$name
		);
	}

	public function __set($name, $value)
	{
		static $classes = array(
			'class_loader' => 'Tequila_ClassLoader',
			'logger'       => 'Tequila_Logger'
		);

		switch ($name)
		{
		case 'class_loader':
		case 'logger':
			// Only certain variables support type checking..
			assert(isset($classes[$name]));

			$class = $classes[$name];
			if (!($value instanceof $class))
			{
				throw new Tequila_Exception(
					__CLASS__.'::'.$name.' must be an instance of '.$class
				);
			}

			$name = '_'.$name;
			$this->$name = $value;
			break;
		default:
			throw new Tequila_Exception(
				'Setting incorrect property: '.__CLASS__.'::'.$name
			);
		}
	}

	/**
	 * Starts the interpreter's loop (prompt → execute).
	 *
	 * @throws Tequila_Exception If Tequila is already running.
	 *
	 * @todo Unit tests.
	 */
	public function start()
	{
		if ($this->is_running)
		{
			throw new Tequila_Exception(__CLASS__.' is already running');
		}

		$this->_is_running = true;

		do {
			$string = $this->prompt($this->prompt);

			// Reading error.
			if ($string === false)
			{
				$this->writeln();
				$this->stop();

				return;
			}

			// To make the  log as close as possible as  the user screen, writes
			// the given data.
			$this->_logger->log($string, Tequila_Logger::NOTICE);

			try
			{
				$result = $this->executeCommand($string);

				if ($result !== null)
				{
					$this->writeln($result);
				}
			}
			catch (Tequila_Exception $e)
			{
				$this->writeln($e->getMessage(), true);
			}
			catch (Exception $e)
			{
				$this->writeln(get_class($e).': '.$e->getMessage(), true);
			}
		} while ($this->_is_running);
	}

	/**
	 * Stops the interpreter's loop.
	 *
	 * @todo Unit tests.
	 */
	public function stop()
	{
		$this->_is_running = false;
	}

	////////////////////////////////////////
	// Tools.

	/**
	 * Returns an array containing all the available methods of $class.
	 *
	 * Available methods are public and do not start with a “_”.
	 *
	 * @return array
	 */
	public function getAvailableMethods(ReflectionClass $class)
	{
		$methods = array();

		foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC) as $method)
		{
			$name = $method->getName();

			if ($name[0] !== '_')
			{
				$methods[] = $name;
			}
		}

		return $methods;
	}

	/**
	 * Returns the class called $class_name.
	 *
	 * If the class is undefined, this method will try to load it.
	 *
	 * @param string $class_name
	 *
	 * @return ReflectionClass
	 *
	 * @throws Tequila_NoSuchClass If the class could not be found.
	 */
	public function getClass($class_name)
	{
		if (class_exists($class_name, false))
		{
			if (!isset($this->_loaded_classes[$class_name]))
			{
				// We did not load this class, denies this access.
				throw new Tequila_NoSuchClass($class_name);
			}
		}
		elseif ($this->_class_loader->load($class_name) &&
		        class_exists($class_name, false))
		{
			$this->_loaded_classes[$class_name] = true;
		}
		else
		{
			throw new Tequila_NoSuchClass($class_name);
		}

		return $class = new ReflectionClass($class_name);
	}

	/**
	 * Returns the method called $method_name of the class $class.
	 *
	 * @param ReflectionClass $class
	 * @param string          $method_name
	 *
	 * @return ReflectionMethod
	 *
	 * @throws Tequila_NoSuchMethod If the method could not be found, is private
	 *                              or start with a “_”.
	 */
	public function getMethod(ReflectionClass $class, $method_name)
	{
		if ($method_name[0] === '_')
		{
			throw new Tequila_NoSuchMethod($class->getName(), $method_name);
		}

		try
		{
			$method = $class->getMethod($method_name);

			if (!$method->isPublic())
			{
				throw new Tequila_NoSuchMethod($class->getName(), $method_name);
			}

			return $method;
		}
		catch (ReflectionException $e)
		{
			throw new Tequila_NoSuchMethod($class->getName(), $method_name);
		}
	}

	/**
	 * @param string $command
	 *
	 * @return mixed The result of the executed method.
	 *
	 * @throws Tequila_UnspecifiedClass   The class is not specified.
	 * @throws Tequila_UnspecifiedMethod  The method is not specified.
	 * @throws Tequila_NoSuchClass        If the class could not be found.
	 * @throws Tequila_NoSuchMethod       If the method could not be found.
	 * @throws Tequila_NotEnoughArguments If  the method  expects more arguments
	 *                                    than those provided.
	 */
	public function executeCommand($command)
	{
		$command = rtrim($command, "\n");

		// TODO: handle multi-line parsing.
		$entries = Tequila_Parser::parseString($command);

		// Nothing significant has been entered.
		if (($entries === false) || (($n = count($entries)) === 0))
		{
			throw new Tequila_UnspecifiedClass();
		}

		$this->addToHistory($command);

		if ($n === 1)
		{
			throw new Tequila_UnspecifiedMethod($entries[0]);
		}

		return $this->execute($entries[0], $entries[1],
		                      array_slice($entries, 2));
	}

	/**
	 * Instanciates  an  object of  the  class  $class_name  and calls  the  its
	 * $method_name method with $arguments as arguments.
	 *
	 * @throws Tequila_NoSuchClass        If the class could not be found.
	 * @throws Tequila_NoSuchMethod       If the method could not be found.
	 * @throws Tequila_NotEnoughArguments If  the method  expects more arguments
	 *                                    than those provided.
	 *
	 * @return mixed The return value of the method.
	 */
	public function execute($class_name, $method_name, array $arguments = null)
	{
		$class  = $this->getClass($class_name);
		$method = $this->getMethod($class, $method_name);

		$arguments = (array) $arguments;

		$n = $method->getNumberOfRequiredParameters();
		if (count($arguments) < $n)
		{
			throw new Tequila_NotEnoughArguments($class_name, $method_name, $n);
		}

		if ($class->isSubclassOf('Tequila_Module'))
		{
			$object = $class->newInstance($this);
		}
		else
		{
			$object = $class->newInstance();
		}

		return $method->invokeArgs($object, $arguments);
	}

	/**
	 * Writes a string.
	 *
	 * @param string $string The string.
	 * @param boolean $error Whether it is an error message.
	 */
	public function write($string, $error = false)
	{
		fwrite($error ? STDERR : STDOUT, $string);

		$this->_logger->log(
			$string,
			$error ? Tequila_Logger::WARNING : Tequila_Logger::NOTICE
		);
	}

	/**
	 * Writes a string followed by a new line.
	 *
	 * @param string $string The string.
	 * @param boolean $error Whether it is an error message.
	 */
	public function writeln($string = '', $error = false)
	{
		$this->write($string.PHP_EOL, $error);
	}

	/**
	 * Replaces variables by their value in a configuration entry.
	 *
	 * A variable has the following format: @A_VARIABLE@.
	 *
	 * Currently the following replacement are done:
	 * - @USER@: the user name (not the $USER environment variable);
	 * - @$name@: by the environment variable called $name (if exists).
	 */
	public function parseConfigEntry($entry)
	{
		if (is_array($entry))
		{
			return array_map(array($this, 'parseConfigEntry'), $entry);
		}

		return preg_replace_callback(
			'/@([A-Z_]+)@/',
			array($this, '_getConfigVariables'),
			$entry
		);
	}

	////////////////////////////////////////
	// History manipulation

	/**
	 * Adds a line to the history.
	 *
	 * @param string $line
	 */
	public abstract function addToHistory($line);

	/**
	 * Clears the history.
	 */
	public abstract function clearHistory();

	protected function __construct()
	{
		$user_info = posix_getpwuid(posix_getuid());
		$this->_user = $user_info['name'];

		$this->class_loader = new Tequila_ClassLoader_Void();
		$this->logger       = new Tequila_Logger_Void();
	}

	private
		$_class_loader,
		$_is_running     = false,
		$_loaded_classes = array(), // Used for security purposes.
		$_logger,
		$_user;

	private function _getConfigVariables(array $matches)
	{
		switch ($matches[1])
		{
		case 'USER':
			return $this->user;
		}

		$value = getenv($matches[1]);

		// If no matches: no replacements.
		return ($value !== false ? $value : $matches[0]);
	}
}
