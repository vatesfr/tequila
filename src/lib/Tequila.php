<?php

/**
 * This is Tequila's main class (obviously).
 *
 * @author Julien Fontanet <julien.fontanet@isonoe.net>
 *
 * @property Tequila_ClassLoader $classLoader
 * @property Tequila_Logger      $logger
 * @property Tequila_Reader      $reader
 * @property Tequila_Writer      $writer
 *
 * @property-read array          $history
 * @property-read boolean        $isRunning
 * @property-read string         $user
 * @property-read Tequila_Parser $parser
 */
class Tequila
{

    // If we want to set these properties private, we should use the “__get” and
    // “__set” magic methods to keep API compatibility.
    public $prompt;
    public $variables = array();

    public function __construct(
    Tequila_ClassLoader $class_loader = null, Tequila_Logger $logger = null, Tequila_Reader $reader = null, Tequila_Writer $writer = null
    )
    {
        $this->_user = getenv('SUDO_USER');
        if ($this->_user === false)
        {
            $user_info = posix_getpwuid(posix_getuid());
            $this->_user = $user_info['name'];
        }
        $this->prompt = $this->_user . '> ';

        if ($class_loader !== null)
        {
            $this->classLoader = $class_loader;
        }
        else
        {
            $this->classLoader = new Tequila_ClassLoader_Void;
        }

        if ($logger !== null)
        {
            $this->logger = $logger;
        }
        else
        {
            $this->logger = new Tequila_Logger_Void;
        }

        if ($reader !== null)
        {
            $this->reader = $reader;
        }
        else
        {
            $this->reader = Tequila_Reader::factory();
        }

        if ($writer !== null)
        {
            $this->writer = $writer;
        }
        else
        {
            $this->writer = new Tequila_Writer_Plain;
        }

        $this->_parser = new Tequila_Parser;
    }

    /**
     * @todo Unit tests.
     */
    public function __get($name)
    {
        switch ($name)
        {
            case 'class_loader':
                return $this->classLoader; // Compatibility.
            case 'is_running':
                return $this->isRunning; // Compatibility
            case 'classLoader':
            case 'history':
            case 'isRunning':
            case 'logger':
            case 'reader':
            case 'user':
            case 'writer':
                return $this->{'_' . $name};
        }

        throw new Tequila_Exception(
            'Getting incorrect property: ' . __CLASS__ . '::' . $name
        );
    }

    public function __set($name, $value)
    {
        static $classes = array(
        'classLoader' => 'Tequila_ClassLoader',
        'logger' => 'Tequila_Logger',
        'reader' => 'Tequila_Reader',
        'writer' => 'Tequila_Writer',
        );

        if ($name === 'class_loader')
        {
            $name = 'classLoader'; // Compatibility.
        }

        switch ($name)
        {
            case 'classLoader':
            case 'logger':
            case 'reader':
            case 'writer':
                // Only certain variables support type checking..
                assert(isset($classes[$name]));

                $class = $classes[$name];
                if (!($value instanceof $class))
                {
                    throw new Tequila_Exception(
                        __CLASS__ . '::' . $name . ' must be an instance of ' . $class
                    );
                }

                $name = '_' . $name;
                $this->$name = $value;
                break;
            default:
                throw new Tequila_Exception(
                    'Setting incorrect property: ' . __CLASS__ . '::' . $name
                );
        }
    }

    /**
     * Starts the interpreter's loop (prompt → execute).
     *
     * @throws Tequila_Exception If Tequila is already running.
     *
     * @todo Unit tests.
     */
    public function start()
    {
        if ($this->isRunning)
        {
            throw new Tequila_Exception(__CLASS__ . ' is already running');
        }

        $this->_isRunning = true;

        do
        {
            $string = $this->prompt($this->prompt);

            // Reading error.
            if ($string === false)
            {
                $this->writeln();
                $this->stop();

                return;
            }

            // To make the  log as close as possible as  the user screen, writes
            // the given data.
            $this->_logger->log($string, Tequila_Logger::NOTICE);

            try
            {
                $result = $this->executeCommand($string);

                if ($result !== null)
                {
                    $this->writeln(self::prettyFormat($result));
                }
            }
            catch (Tequila_IncorrectSyntax $e)
            {
                $offset = strlen($this->prompt) + $e->index;

                $this->writeln(str_repeat(' ', $offset) . '▲');
                $this->writeln(str_repeat('─', $offset) . '┘');
                $this->writeln('incorrect syntax at ' . $e->index . ': ' . $e->getMessage());
            }
            catch (Tequila_Exception $e)
            {
                $this->writeln($e->getMessage(), true);
            }
            catch (Exception $e)
            {
                $this->writeln(get_class($e) . ': ' . $e->getMessage(), true);
            }
        }
        while ($this->_isRunning);
    }

    /**
     * Stops the interpreter's loop.
     *
     * @todo Unit tests.
     */
    public function stop()
    {
        $this->_isRunning = false;
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
        elseif ($this->_classLoader->load($class_name) &&
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
     * @throws Tequila_NoSuchMethod If the method could not be found, is private
     *                              or start with a “_”.
     */
    public function getMethod(ReflectionClass $class, $method_name)
    {
        if (empty($method_name) || ($method_name[0] === '_'))
        {
            throw new Tequila_NoSuchMethod($class->getName(), $method_name);
        }

        try
        {
            $method = $class->getMethod($method_name);

            if (!$method->isPublic())
            {
                throw new Tequila_NoSuchMethod($class->getName(), $method_name);
            }

            return $method;
        }
        catch (ReflectionException $e)
        {
            throw new Tequila_NoSuchMethod($class->getName(), $method_name);
        }
    }

    /**
     * Executes a given command.
     *
     * @param Tequila_Parser_Command|string $command
     *
     * @return mixed The result of the executed method.
     *
     * @throws Tequila_UnspecifiedClass   The class is not specified.
     * @throws Tequila_UnspecifiedMethod  The method is not specified.
     * @throws Tequila_NoSuchClass        If the class could not be found.
     * @throws Tequila_NoSuchMethod       If the method could not be found.
     * @throws Tequila_NotEnoughArguments If  the method  expects more arguments
     *                                    than those provided.
     */
    public function executeCommand($command)
    {
        ($command instanceof Tequila_Parser_Command)
            or $command = $this->parseCommand($command);

        return $this->evaluate($command);
    }

    /**
     * @todo Write documentation.
     */
    public function evaluate($node)
    {
        if (is_scalar($node))
        {
            return $node;
        }

        if (is_array($node))
        {
            foreach ($node as &$entry)
            {
                $entry = $this->evaluate($entry);
            }

            return $node;
        }

        if ($node instanceof Tequila_Parser_Variable)
        {
            if (isset($this->variables[$node->name]))
            {
                return $this->variables[$node->name];
            }

            return null;
        }

        if ($node instanceof Tequila_Parser_Command)
        {
            $class = $this->evaluate($node->class);
            $method = $this->evaluate($node->method);
            $args = $this->evaluate($node->args);

            return $this->execute($class, $method, $args);
        }
    }

    /**
     * Instanciates  an  object of  the  class  $class_name  and calls  the  its
     * $method_name method with $arguments as arguments.
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
        $class = $this->getClass($class_name);
        $method = $this->getMethod($class, $method_name);

        $arguments = (array) $arguments;

        $n = $method->getNumberOfRequiredParameters();
        if (count($arguments) < $n)
        {
            throw new Tequila_NotEnoughArguments($class_name, $method_name, $n);
        }

        if ($class->isSubclassOf('Tequila_Module'))
        {
            /*
             * Tequila  modules  are  instanciated  through  the  static  method
             * “_factory($tequila, $class_name)”.
             */
            $object = call_user_func(array($class_name, '_factory'), $this, $class_name);

            if (!($object instanceof $class_name))
            {
                throw new Tequila_Exception(
                    'Tequila module instanciation failed: ' . $class_name
                );
            }
        }
        else
        {
            $object = $class->newInstance();
        }

        return $method->invokeArgs($object, $arguments);
    }

    /**
     * Parses a command.
     *
     * @param string $command
     *
     * @return Tequila_Parser_Command The command parsed as an object.
     *
     * @param throws Tequila_IncorrectSyntax   If the parsing failed.
     * @param throws Tequila_UnspecifiedClass  If no class was specified.
     * @param throws Tequila_UnspecifiedMethod If no method was specified.
     */
    public function parseCommand($command)
    {
        $command = rtrim($command, PHP_EOL);

        return $this->_parser->parse($command);
    }

    public function prompt($prompt)
    {
        return $this->_reader->read($this, $prompt);
    }

    public function promptSecure($prompt, array $legalValues, $caseInsensitive = false)
    {
        if ($caseInsensitive)
        {
            foreach ($legalValues as &$value)
            {
                $value = strtolower($value);
            }
        }

        do
        {
            $answer = trim($this->prompt($prompt));
            if ($caseInsensitive)
            {
                $answer = strtolower($answer);
            }
        }
        while (!in_array($answer, $legalValues));

        return $answer;
    }

    /**
     * Writes a string.
     *
     * @param string $string The string.
     * @param boolean $error Whether it is an error message.
     */
    public function write($string, $error = false)
    {
        $this->_writer->write($string, $error);

        $this->_logger->log(
            $string, $error ? Tequila_Logger::WARNING : Tequila_Logger::NOTICE
        );
    }

    /**
     * Writes a string followed by a new line.
     *
     * @param string $string The string.
     * @param boolean $error Whether it is an error message.
     */
    public function writeln($string = '', $error = false)
    {
        $this->write($string . PHP_EOL, $error);
    }

    /**
     * Replaces variables by their value in a configuration entry.
     *
     * A variable has the following format: @A_VARIABLE@.
     *
     * Currently the following replacement are done:
     * - @USER@: the user name (not the $USER environment variable);
     * - @$name@: by the environment variable called $name (if exists).
     */
    public function parseConfigEntry($entry)
    {
        if (is_array($entry))
        {
            return array_map(array($this, 'parseConfigEntry'), $entry);
        }

        return preg_replace_callback(
                '/@([A-Z_]+)@/', array($this, '_getConfigVariables'), $entry
        );
    }

    /**
     * @todo Write documentation
     */
    public function setOption($name, $value)
    {
        static $true_strings = array(
        'on' => true,
        'true' => true,
        'yes' => true,
        );

        if (is_array($value))
        {
            foreach ($value as $value)
            {
                $this->setOption($name, $value);
            }
            return;
        }

        is_string($value)
            and $value = $this->parseConfigEntry($value);

        switch ($name)
        {
            case 'include-dirs':
                if (!($this->_classLoader instanceof Tequila_ClassLoader_Gallic))
                {
                    throw new Exception('incompatible class loader');
                }
                $this->_classLoader->addDirectory($value);
                break;
            case 'log-file':
                $this->_logger = new Tequila_Logger_File($value);
                break;
            case 'quote-strings':
                is_string($value)
                    and $value = isset($true_strings[strtolower($value)]);
                $this->_quoteStrings = $value;
                break;
            default:
                throw new Exception('Invalid option: ' . $name);
        }
    }

    /**
     * @todo Write documentation.
     */
    public function setVariable($name, $value)
    {
        if (isset($value))
        {
            $this->variables[$name] = $value;
        }
        else
        {
            unset($this->variables[$name]);
        }
    }

    /**
     * Format the given value to a nice, easily readable format.
     *
     * @param string $indent
     *
     * @return string
     */
    public function prettyFormat($value, $indent = '')
    {
        $next_indent = $indent . '    ';

        if (is_array($value))
        {
            $str = 'array [' . count($value) . '] (' . PHP_EOL;
            foreach ($value as $key => $entry)
            {
                $str .=
                    $next_indent . $this->prettyFormat($key) . ' => ' .
                    $this->prettyFormat($entry, $next_indent) . ',' . PHP_EOL;
            }
            return ($str . $indent . ')');
        }

        if (is_bool($value))
        {
            return ($value ? 'true' : 'false');
        }

        if (is_null($value))
        {
            return 'null';
        }

        if (is_string($value)
            || (is_object($value) && method_exists($value, '__toString')))
        {
            $value = (string) $value;

            if (!$this->_quoteStrings
                && ($indent === '')) // First level
            {
                return rtrim($value); // Prevents unecessary line feeds.
            }

            // Protect quotes and antislashes and wraps with quotes.
            return "'" . preg_replace('/(?=[\\\\\'])/', '\\', (string) $value) . "'";
        }

        // Indents correctly.
        return preg_replace('/\r\n?|\n\r?/', '$0' . $indent, var_export($value, true));
    }

    private
        $_classLoader,
        $_history = array(),
        $_isRunning = false,
        $_loaded_classes = array(), // Used for security purposes.
        $_logger,
        $_parser,
        $_quoteStrings = true,
        $_reader,
        $_user,
        $_writer;

    private function _getConfigVariables(array $matches)
    {
        switch ($matches[1])
        {
            case 'USER':
                return $this->user;
        }

        $value = getenv($matches[1]);

        // If no matches: no replacements.
        return ($value !== false ? $value : $matches[0]);
    }

}
