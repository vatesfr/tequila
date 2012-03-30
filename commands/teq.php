<?php

/**
 * @author Julien Fontanet <julien.fontanet@isonoe.net>
 */

/**
 * This class contains various useful commands/methods for the Tequila Shell.
 *
 * It can  be used  as a demonstration  and a  reference of the  flexibility and
 * abilities of Tequila.
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
			$this->_tequila->writeln(str_pad($i, $n).' '.$command);
			++$i;
		}
	}

	/**
	 * Writes all its arguments on different lines.
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
}
