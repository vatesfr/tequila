<?php

/**
 * This is Tequila's main class (obviously).
 */
class Tequila
{
	// If we want to set these properties private, we should use the “__get” and
	// “__set” magic methods to keep API compatibility.
	public
		$prompt = 'tequila> ',
		$include_dirs = array();

	public function __construct()
	{
		$user_info = posix_getpwuid(posix_getuid());
		$this->_user = $user_info['name'];
	}

	public function __get($name)
	{
		switch ($name)
		{
		case 'history':
			return $this->_history;
		}
	}


	/**
	 * Starts the interpreter's loop (prompt → parse → execute).
	 */
	public function start()
	{
		$this->_is_running = true;

		do {
			$this->write($this->prompt);

			$string = $this->readln();
			if ($string === false)
			{
				$this->stop();
				return;
			}

			$string = rtrim($string, "\n");

			// TODO: handle multi-line parsing.
			$entries = Tequila_Parser::parseString($string);

			if (($entries === false) || (($n = count($entries)) === 0))
			{
				continue;
			}

			$this->_history[] = $string;

			if ($n === 1)
			{
				$this->writeln('Missing method', STDERR);
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
				$this->writeln($e->getMessage(), STDERR);
			}
			catch (Exception $e)
			{
				$this->writeln(get_class($e).': '.$e->getMessage(), STDERR);
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
	 * Returns   a  ReflectionClass   instance  describing   the   class  called
	 * $class_name.
	 *
	 * If the class is undefined, this method will try to load it.
	 *
	 * @throws Tequila_NoSuchClass If the class could not be found.
	 */
	public function getClass($class_name)
	{
		if (!(class_exists($class_name, false)
		      || (Gallic_Loader::loadClass($class_name, $this->include_dirs)
		          && class_exists($class_name, false))))
		{
			throw new Tequila_NoSuchClass($class_name);
		}

		return $class = new ReflectionClass($class_name);
	}

	/**
	 * Returns  a   ReflectionMethod  instance  describing   the  method  called
	 * $method_name of the class $class.
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
	 * @throws Tequila_NoSuchClass If the class could not be found.
	 * @throws Tequila_NoSuchMethod If the method could not be found.
	 * @throws  Tequila_NotEnoughArgument If the  method expects  more arguments
	 *                                    than those provided.
	 *
	 * @return The return value of the method.
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
	 * Reads a single ligne from the specified $file_handle.
	 *
	 * @return The line read or false if an error occured.
	 */
	public function readln($file_handle = STDIN)
	{
		return fgets($file_handle);
	}

	/**
	 * Writes a string to the specified $file_handle.
	 */
	public function write($message = '', $file_handle = STDOUT)
	{
		fwrite($file_handle, $message);
	}

	/**
	 * Writes a string followed by a new line to the specified $file_handle.
	 */
	public function writeln($message = '', $file_handle = STDOUT)
	{
		$this->write($message.PHP_EOL);
	}

	private
		$_history    = array(),
		$_hooks      = array(),
		$_is_running = false;
}
