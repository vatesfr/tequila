<?php

/**
 * @author Julien Fontanet <julien.fontanet@isonoe.net>
 */

/**
 *
 */
final class _redirect_writer extends Tequila_Writer
{
	public function __construct($out_hdl, $err_hdl)
	{
		$this->_outHdl = $out_hdl;
		$this->_errHdl = $err_hdl;
	}

	public function write($string, $error)
	{
		fwrite(
			$error ? $this->_outHdl : $this->_errHdl,
			$string
		);
	}

	private $_outHdl;

	private $_errHdl;
}

/**
 *
 */
final class _redirect_reader extends Tequila_Reader
{
	public function __construct($in_hdl)
	{
		$this->_inHdl = $in_hdl;
	}

	public function read(Tequila $tequila, $prompt)
	{
		return fgets($this->_inHdl);
	}

	private $_inHdl;
}

/**
 * This class contains various useful commands/methods for the Tequila Shell.
 *
 * It can  be used  as a demonstration  and a  reference of the  flexibility and
 * abilities of Tequila.
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
		$this->_tequila->writer = new _redirect_writer($handle, $handle);

		try
		{
			$this->_tequila->execute($class_name, $method_name, array_slice($args, 3));
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
		$this->_tequila->reader = new _redirect_reader($handle);

		try
		{
			$result = $this->_tequila->execute($class_name, $method_name, array_slice($args, 3));

			if ($result !== null)
			{
				$this->_tequila->writeln(self::prettyFormat($result));
			}
		}
		catch (Exception $e)
		{}

		// restore
		$this->_tequila->reader = $orr;
	}
}
