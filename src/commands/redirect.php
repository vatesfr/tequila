<?php

/**
 * @author Julien Fontanet <julien.fontanet@isonoe.net>
 */

/**
 * This class contains various useful commands/methods for the Tequila
 * Shell.
 *
 * It can be used as a demonstration and a reference of the
 * flexibility and abilities of Tequila.
 */
final class redirect extends Tequila_Module
{
	/**
	 * Launches a command which writes its outputs to a file.
	 *
	 * @param string $file
	 * @param string $class_name
	 * @param string $method_name
	 * @param string $... Arguments.
	 */
	public function to($file, $class_name, $method_name)
	{
		$args = func_get_args();

		// original writer
		$orw = $this->_tequila->writer;

		$handle = fopen($file, 'w');
		if ($handle === false)
		{
			throw new Exception('File could not be opened: '.$file);
		}

		$this->_tequila->writer = new Tequila_Writer_Stream($handle);

		try
		{
			$result = $this->_tequila->execute($class_name, $method_name, array_slice($args, 3));

			if ($result !== null)
			{
				$this->_tequila->write($this->_tequila->prettyFormat($result).PHP_EOL);
			}
		}
		catch (Exception $e)
		{}

		// restore
		$this->_tequila->writer = $orw;
	}

	/**
	 * Launches a command which reads its inputs from a file.
	 *
	 * @param string $file
	 * @param string $class_name
	 * @param string $method_name
	 * @param string $... Arguments.
	 */
	public function from($file, $class_name, $method_name)
	{
		$args = func_get_args();

		// original reader
		$orr = $this->_tequila->reader;

		$handle = fopen($file, 'r');
		if ($handle === false)
		{
			throw new Exception('File could not be opened: '.$file);
		}

		$this->_tequila->reader = new Tequila_Reader_Stream($handle);

		try
		{
			$result = $this->_tequila->execute($class_name, $method_name, array_slice($args, 3));

			if ($result !== null)
			{
				$this->_tequila->writeln($this->_tequila->prettyFormat($result));
			}
		}
		catch (Exception $e)
		{}

		// restore
		$this->_tequila->reader = $orr;
	}
}
