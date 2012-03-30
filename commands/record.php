<?php

/**
 * @author Julien Fontanet <julien.fontanet@isonoe.net>
 */

/**
 * This specific Tequila_Writer is needed to capture commands results.
 */
final class _record_writer extends Tequila_Writer
{
	public function __construct(Tequila_Writer $writer = null)
	{
		$this->_writer = $writer;
	}

	public function write($string, $error)
	{
		$this->_output .= $string;

		isset($this->_writer)
			and $this->_writer->write($string, $error);
	}

	public function pop()
	{
		$output = $this->_output;

		$this->_output = '';

		return $output;
	}

	private $_output = '';

	private $_writer;
}

/**
 * This exception is used to stops the recording.
 */
final class _record_stop extends Exception
{}

/**
 * This module provides a recording system which allows one to record a bunch of
 * commands and then replay them latter.
 */
final class record extends Tequila_Module
{
	/**
	 * Starts recording commands.
	 *
	 * @param string $file
	 *
	 * @todo Mutualise some code with Tequila::start() and
	 *     Tequila::executeCommand().
	 */
	public function start($file)
	{
		$handle = @fopen($file, 'w');

		if ($handle === false)
		{
			$this->_tequila->writeln('Failed to open: '.$file, true);
			return;
		}

		$or_writer = $this->_tequila->writer;
		$my_writer = new _record_writer($or_writer);

		$this->_tequila->writer = $my_writer;

		for (;;)
		{
			$command = rtrim($this->_tequila->prompt('recording> '), PHP_EOL);

			// Reading error.
			if ($command === false)
			{
				break;
			}

			try
			{
				$my_writer->pop();

				$this->_tequila->executeCommand($command);

				if ($result = rtrim($my_writer->pop(), PHP_EOL))
				{
					$result = preg_replace('/^/m', '# ', $result).PHP_EOL;
				}

				fwrite($handle, $command.PHP_EOL.$result.PHP_EOL);
			}
			catch (_record_stop $e)
			{
				break;
			}
			catch (Tequila_Exception $e)
			{
				$this->_tequila->writeln($e->getMessage(), true);
			}
			catch (Exception $e)
			{
				$this->_tequila->writeln(get_class($e).': '.$e->getMessage(), true);
			}
		}

		$this->_tequila->writer = $or_writer;

		fclose($handle);
	}

	/**
	 * Stops the recording.
	 */
	public function stop()
	{
		throw new _record_Stop;
	}

	/**
	 * Play an existing recording.
	 *
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

			if (!$this->_tequila->parseCommand($line))
			{
				continue;
			}

			$this->_tequila->writeln('>>> '.$line);

			try
			{
				$this->_tequila->executeCommand($line);
			}
			catch (Tequila_UnspecifiedClass $e)
			{}
		}

		fclose($handle);
	}
}
