<?php

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

	public function history()
	{
		var_dump($this->_tequila->history);
	}

	public function quit()
	{
		$this->_tequila->writeln('Bye!');
		$this->_tequila->stop();
	}
}