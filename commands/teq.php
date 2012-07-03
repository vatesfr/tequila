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
final class teq extends Tequila_Module
{

    /**
     * When called with  one argument, lists the available  commands for a given
     * class, otherwise lists the parameters of a given command.
     *
     * @param string      $class_name
     * @param string|null $method_name
     */
    public function describe($class_name, $method_name = null)
    {
        $class = $this->_tequila->getClass($class_name);

        if ($method_name === null)
        {
            $this->_tequila->writeln(self::_getDocComment($class));

            $this->_tequila->writeln();

            $methods = $this->_tequila->getAvailableMethods($class);
            $this->_tequila->writeln(count($methods) . ' method(s) available');
            foreach ($methods as $method)
            {
                $this->_tequila->writeln('- ' . $method);
            }

            return;
        }

        $method = $this->_tequila->getMethod($class, $method_name);

        $this->_tequila->writeln(self::_getDocComment($method));

        $this->_tequila->writeln();

        $parameters = $method->getParameters();
        $this->_tequila->writeln(count($parameters) . ' parameter(s)');
        foreach ($parameters as $parameter)
        {
            $this->_tequila->write('- ' . $parameter->getName());

            if ($parameter->isOptional())
            {
                $value = $parameter->getDefaultValue();
                $this->_tequila->write(' (' . var_export($value, true) . ')');
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
            $this->_tequila->writeln(str_pad($i, $n) . ' ' . $command);
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

    /**
     * This command does strictly nothing and may be used to write recordable
     * comments.
     */
    public function C()
    {

    }

    /**
     * Prints the content of a file.
     *
     * @todo Handle multiple files.
     * @todo Maybe replace this command by a generic “exec”.
     *
     * @param string $file The file to print.
     *
     * @throws Exception If the file does not exist or is not readable.
     */
    public function cat($file)
    {
        if (!file_exists($file))
        {
            throw new Exception('File does not exist.');
        }

        if (!is_readable($file))
        {
            throw new Exception('File is not readable.');
        }

        $this->_tequila->writeln(file_get_contents($file));
    }

    /**
     * Waits for a few seconds.
     */
    public function sleep($seconds)
    {
        sleep($seconds);
    }

    /**
     * Prints a date with a given format.
     *
     * @param string $date   The date to print (default is “now”).
     * @param string $format The format to use to print the date (“default is c”).
     *
     * @return string
     */
    public function date($date = null, $format = null)
    {
	    if (isset($date))
	    {
		    // @todo Handle false on incorrect date.
		    $date = strtotime($date);
	    }
	    else
	    {
		    $date = time();
	    }

	    isset($format)
		    or $format = 'c';

	    return date($format, $date);
    }

    /**
     * Waits for the user to press enter.
     */
    public function pause()
    {
	    $this->_tequila->prompt('');
    }

    private static function _getDocComment($node)
    {
        $comment = $node->getDocComment();

        if ($comment === false)
        {
            return null;
        }

        return preg_replace(
            array(
                '#^\s*/\*\*\s*#', // Remove “/**” at the begining.
                '#\s*\*/$#',      // Remove “*/” at the end.
                '#^\s*\* ?#m',    // Remove “* ” on each line.
            ),
            '',
            $comment
        );
    }
}
