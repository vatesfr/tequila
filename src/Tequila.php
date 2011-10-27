<?php

/**
 * This is Tequila's main class (obviously).
 */
abstract class Tequila
{
	// If we want to set these properties private, we should use the “__get” and
	// “__set” magic methods to keep API compatibility.
	public
		$class_loader,
		$logger,
		$prompt = 'tequila> ';

	public function __construct()
	{
		$user_info = posix_getpwuid(posix_getuid());
		$this->_user = $user_info['name'];
	}

	public function __get($name)
	{
		switch ($name)
		{
		case 'is_running':
			$name = '_'.$name;
			return $this->$name;
		}

		throw new Exception('Getting incorrect property: '.__CLASS__.'::'.$name);
	}

	/**
	 * Starts the interpreter's loop (prompt → parse → execute).
	 */
	public function start()
	{
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

			$string = rtrim($string, "\n");

			// TODO: handle multi-line parsing.
			$entries = Tequila_Parser::parseString($string);

			// Nothing significant has been entered.
			if (($entries === false) || (($n = count($entries)) === 0))
			{
				continue;
			}

			$this->addToHistory($string);

			if ($n === 1)
			{
				$this->writeln('Missing method', true);
				continue;
			}

			try
			{
				$result = $this->execute($entries[0], $entries[1],
				                         array_slice($entries, 2));

				if ($result !== null)
				{
					$this->writeln($result);
				}
				elseif (isset($this->messages["null_value"]))
				{
					$this->writeln($this->messages["null_value"]);
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
		elseif ($this->class_loader->load($class_name) &&
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
	 * @throws Tequila_NoSuchMethod If the method could not be found.
	 */
	public function getMethod(ReflectionClass $class, $method_name)
	{
		try
		{
			return $class->getMethod($method_name);
		}
		catch (ReflectionException $e)
		{
			throw new Tequila_NoSuchMethod($class->getName(), $method_name);
		}
	}

	/**
	 * Instanciates  an  object of  the  class  $class_name  and calls  the  its
	 * $method_name method with  the name of the current  user and $arguments as
	 * arguments.
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

		// Do not count the first parameter which is the user.
		$n = $method->getNumberOfRequiredParameters() - 1;
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

		array_unshift($arguments, $this->_user);

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

		if ($this->logger !== null)
		{
			$this->logger->log($string, $error ?
			                   Tequila_Logger::WARNING :
			                   Tequila_Logger::NOTICE);
		}
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

	private
		$_is_running     = false,
		$_loaded_classes = array(), // Used for security purposes.
		$_logger,
		$_user;
}
