<?php

/**
 * @author Julien Fontanet <julien.fontanet@isonoe.net>
 */

/**
 * This specific Tequila_Writer is needed to capture commands results.
 */
final class _record_start_writer extends Tequila_Writer
{

	public function __construct(Tequila_Writer $writer)
    {
        $this->_writer = $writer;
    }

    public function write($string, $error)
    {
        $this->_output .= $string;
        $this->_writer->write($string, $error);
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
 *
 */
final class _record_play_writer extends Tequila_Writer
{
	public function __construct(Tequila_Writer $writer)
	{
		$this->_writer = $writer;
	}

	public function write($string, $error)
	{
		if ($string === '')
		{
			return;
		}

		// Appends “# ” to line feeds unless it's at the end.
		$string = preg_replace('/(?<=\n)(?!$)/', '# ', $string);

		// Prepends “# ” to the string only if it is the begining of a line.
		if ($this->_beginingOfLine)
		{
			$string = '# '.$string;
		}
		$this->_beginingOfLine = ($string[strlen($string) - 1] === "\n");

		$this->_writer->write($string, $error);
	}

	private $_beginingOfLine = true;
	private $_writer;
}

/**
 * This exception is used to stops the recording.
 */
final class _record_stop extends Exception
{

    public function __construct()
    {
        /*
         * This message will be shown if the exception is not caught, ie. there
         * is no current recording session.
         */
        parent::__construct('we are not recording');
    }

}

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
     * @param string $mode 'w' for truncating the file at record start, 'a' for
     *     adding record at the end of file.  'w' by default.
     *
     * @todo Mutualise some code with Tequila::start() and
     *     Tequila::executeCommand().
     */
    public function start($file, $mode = NULL)
    {
        if ($mode === NULL)
        {
            $mode = 'w';
        }

        if ($mode !== 'a' && $mode !== 'w')
        {
            throw new Tequila_Exception('Record mode must be \'w\' for overwriting(default) or \'a\' for adding');
        }

        $handle = @fopen($file, $mode);

        if ($handle === false)
        {
            $this->_tequila->writeln('Failed to open: ' . $file, true);
            return;
        }

        $or_writer = $this->_tequila->writer;
        $my_writer = new _record_start_writer($or_writer);

        $this->_tequila->writer = $my_writer;

        for (;;)
        {
            $command = rtrim($this->_tequila->prompt('recording> '), PHP_EOL);

            // Reading error.
            if ($command === false)
            {
                $this->_tequila->stop();
                break;
            }

            try
            {
                $my_writer->pop();

                $retval = $this->_tequila->executeCommand($command);
                isset($retval)
                    and $this->_tequila->write($this->_tequila->prettyFormat($retval).PHP_EOL);

                $result = $my_writer->pop();
            }
            catch (_record_stop $e)
            {
                break;
            }
            catch (Tequila_Exception $e)
            {
                $this->_tequila->writeln($e->getMessage(), true);
                continue;
            }
            catch (Exception $e)
            {
                $this->_tequila->writeln(get_class($e) . ': ' . $e->getMessage(), true);

                $result = $my_writer->pop();

                $answer = trim($this->_tequila->prompt(
                    PHP_EOL
                    .'The previous command raised an error.'.PHP_EOL
                    .'Do you want to record it anyway? [y/N] '
                ));
                if (strcasecmp($answer, 'y') !== 0)
                {
                    continue;
                }
            }

            if ($result = rtrim($result, PHP_EOL))
            {
                $result = preg_replace('/^/m', '# ', $result) . PHP_EOL;
            }

            // @todo even if it is only a comment, records it.
            fwrite($handle, $command . PHP_EOL . $result . PHP_EOL);
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
     * @param boolean $continueOnFailure (default is false).
     *
     * @todo Add a verbose mode and only display commands in this mode.
     */
    public function play($file, $continueOnFailure = NULL)
    {
        if ($continueOnFailure === NULL)
        {
            $continueOnFailure = false;
        }

        $handle = @fopen($file, 'r');

        if ($handle === false)
        {
            $this->_tequila->writeln('Failed to open: ' . $file, true);
            return;
        }

        $orw = $this->_tequila->writer;       // Original writer.
        $myw = new _record_play_writer($orw); // My writer.

        $this->_tequila->writer = $myw;

        while (($line = fgets($handle)) !== false)
        {
            try
            {
                $line = rtrim(ltrim($line), PHP_EOL);

                if (empty($line) || ($line[0] === '#'))
                {
	                continue;
                }

                $orw->writeln($line, false);

                $retval = $this->_tequila->executeCommand($line);

                isset($retval)
	                and $myw->write($this->_tequila->prettyFormat($retval).PHP_EOL);

                $orw->write(PHP_EOL, false);
            }
            catch (Exception $e)
            {
                if (!$continueOnFailure)
                {
                    fclose($handle);
                    $this->_tequila->writer = $orw;

                    throw $e;
                }

                $myw->write(get_class($e). ': ' . $e->getMessage());
            }
        }

        fclose($handle);
        $this->_tequila->writer = $orw;
    }

}
