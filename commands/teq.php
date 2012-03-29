<?php

/**
 * This class contains various useful commands/methods for the Tequila Shell.
 *
 * It can  be used  as a demonstration  and a  reference of the  flexibility and
 * abilities of Tequila.
 *
 * @author Julien Fontanet <julien.fontanet@isonoe.net>
 */
class teq extends Tequila_Module
{
	/**
	 * When called with  one argument, lists the available  commands for a given
	 * class, otherwise lists the parameters of a given command.
	 *
	 * @param string      $class_name
	 * @param string|null $method_name
	 *
	 * @todo Prints the documentation of the appropriate class/method.
	 */
	public function describe($class_name, $method_name = null)
	{
		$class = $this->_tequila->getClass($class_name);

		if ($method_name === null)
		{
			$methods = $this->_tequila->getAvailableMethods($class);

			$this->_tequila->writeln(count($methods). ' method(s) available');
			foreach ($methods as $method)
			{
				$this->_tequila->writeln('- '.$method);
			}

			return;
		}

		$method = $this->_tequila->getMethod($class, $method_name);

		$parameters = $method->getParameters();

		$this->_tequila->writeln(count($parameters).' parameter(s)');
		foreach ($parameters as $parameter)
		{
			$this->_tequila->write('- '.$parameter->getName());

			if ($parameter->isOptional())
			{
				$value = $parameter->getDefaultValue();
				$this->_tequila->write(' ('.var_export($value, true).')');
			}
			$this->_tequila->writeln();
		}
	}

	/**
	 * Prints the list of the entered commands.
	 */
	public function history()
	{
		$history = $this->_tequila->history;

		$i = 1;
		$n = 1 + (int) log(count($history), 10);
		foreach ($history as $command)
		{
			echo str_pad($i, $n), ' ', $command, PHP_EOL;
			++$i;
		}
	}

	/**
	 *
	 */
	public function writeln()
	{
		$args = func_get_args();
		foreach ($args as $arg)
		{
			$this->_tequila->writeln($arg);
		}
	}

	/**
	 * Stops Tequila.
	 */
	public function quit()
	{
		$this->_tequila->writeln('Bye!');
		$this->_tequila->stop();
	}

	//--------------------------------------

	/**
	 * @param string $file
	 *
	 * @todo Mutualise some code with Tequila::start() and
	 *     Tequila::executeCommand().
	 */
	public function startRecord($file)
	{
		$this->_recording = true;

		$handle = @fopen($file, 'w');

		if ($handle === false)
		{
			$this->_tequila->writeln('Failed to open: '.$file, true);
			return;
		}

		do {
			$string = $this->_tequila->prompt('recording> ');

			// Reading error.
			if ($string === false)
			{
				$this->_tequila->writeln();
				$this->stopRecord();

				return;
			}

			try
			{
				$result = $this->_tequila->executeCommand($string);

				fwrite($handle, $string);

				if ($result !== null)
				{
					$this->_tequila->writeln(self::prettyFormat($result));
				}
			}
			catch (Tequila_Exception $e)
			{
				$this->_tequila->writeln($e->getMessage(), true);
			}
			catch (Exception $e)
			{
				$this->_tequila->writeln(get_class($e).': '.$e->getMessage(), true);
			}
		} while ($this->_recording);

		fclose($handle);
	}

	public function stopRecord()
	{
		$this->_recording = false;
	}

	/**
	 * @param string $file
	 *
	 * @todo Add a verbose mode and only diplay commands in this mode.
	 */
	public function play($file)
	{
		$handle = @fopen($file, 'r');

		if ($handle === false)
		{
			$this->_tequila->writeln('Failed to open: '.$file, true);
			return;
		}

		while (($line = fgets($handle)) !== false)
		{
			$line = rtrim(ltrim($line), PHP_EOL);

			$this->_tequila->writeln();
			$this->_tequila->writeln('>> '.$line);

			$this->_tequila->executeCommand($line);
		}

		fclose($handle);
	}

	/**
	 *
	 */
	private $_recording = false;
}
