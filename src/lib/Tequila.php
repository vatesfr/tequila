<?php

/**
 * This is Tequila's main class (obviously).
 *
 * @author Julien Fontanet <julien.fontanet@isonoe.net>
 *
 * @property Tequila_ClassLoader $classLoader
 * @property Tequila_Writer      $logger
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
    /**
     * String used to indent nested items when displaying complex
     * values such as arrays.
     */
    const INDENT = '    ';

    // If we want to set these properties private, we should use the “__get” and
    // “__set” magic methods to keep API compatibility.
    public $prompt;
    public $variables = array();

    /**
     * Initializes a new Tequila object.
     *
     * @param Tequila_ClassLoader $class_loader The loader used to
     *     load modules (classes). Default is a null loader which
     *     always fails.
     * @param Tequila_Reader $reader The reader used for this shell
     *     instance. Default is a stream reader using the standard
     *     input.
     * @param Tequila_Writer $writer The writer used for this shell
     *     instance. Default is a stream reader using the standard
     *     output for non-error messages and using the standard
     *     error output for error messages.
     */
    public function __construct(
    Tequila_ClassLoader $class_loader = null,
    Tequila_Reader $reader = null,
    Tequila_Writer $writer = null
    )
    {
        $this->classLoader = ($class_loader)
            ? $class_loader
            : new Tequila_ClassLoader_Void;

        $this->reader = ($reader)
            ? $reader
            : new Tequila_Reader_Stream(STDIN);

        $this->_writer = ($writer)
            ? $writer
            : new Tequila_Writer_Stream(STDOUT, STDERR);

        $this->_user = getenv('SUDO_USER');
        if ($this->_user === false)
        {
            $user_info = posix_getpwuid(posix_getuid());
            $this->_user = $user_info['name'];
        }
        $this->prompt = $this->_user . '> ';

        $this->_parser = new Tequila_Parser;
    }

    /**
     * Magic method automatically invoked by PHP when reading a
     * property.
     *
     * @param string $name The name of the property.
     *
     * @throws Tequila_Exception If the property does not exist or is
     *                           not readable.
     *
     * @return mixed The value of the property.
     *
     * @todo Unit tests.
     */
    public function __get($name)
    {
        switch ($name)
        {
            // Compatibility
            case 'class_loader':
                return $this->classLoader;
            case 'is_running':
                return $this->isRunning;
            case 'logger':
                return isset($this->_writer['_logger_'])
                    ? $this->_writer['_logger_']
                    : null;

            case 'classLoader':
            case 'history':
            case 'isRunning':
            case 'reader':
            case 'user':
            case 'writer':
                return $this->{'_' . $name};
        }

        throw new Tequila_Exception(
            'Getting incorrect property: ' . __CLASS__ . '::' . $name
        );
    }

    /**
     * Magic method automatically invoked by PHP when setting a
     * property.
     *
     * @param string $name  The name of the property.
     * @param mixed  $value The new value of the property.
     *
     * @throws Tequila_Exception If the property does not exist or is
     *     not writable, or if the value is invalid for this property.
     *
     * @todo Unit tests.
     */
    public function __set($name, $value)
    {
        if ('logger' === $name)
        {
            if ($this->_writer instanceof Tequila_Writer_Aggregate)
            {
                $this->_writer['_logger_'] = $value;
            }
            else
            {
                $this->_writer = new Tequila_Writer_Aggregate(array(
                    '_default_' => $this->_writer,
                    '_logger_'  => $value,
                ));
            }
        }
        elseif ('writer' === $name)
        {
            if ( !($value instanceof Tequila_Writer) )
            {
                throw new Tequila_Exception(
                    __CLASS__ . '::' . $name . ' must be an instance of Tequila_Writer'
                );
            }

            $this->_writer = $value;
        }
        elseif (('classLoader' === $name)
            || ('class_loader' === $name)) // Compatibility.
        {
            if ( !($value instanceof Tequila_ClassLoader) )
            {
                throw new Tequila_Exception(
                    __CLASS__ . '::' . $name . ' must be an instance of Tequila_ClassLoader'
                );
            }

            $this->_classLoader = $value;
        }
        elseif ('reader' === $name)
        {
            if ( !($value instanceof Tequila_Reader) )
            {
                throw new Tequila_Exception(
                    __CLASS__ . '::' . $name . ' must be an instance of Tequila_Reader'
                );
            }

            $this->_reader = $value;
        }
        else
        {
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

            try
            {
                $result = $this->executeCommand($string);

                if ($result !== null)
                {
                    $this->writeln($this->prettyFormat($result));
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
            if (!isset($this->_loadedClasses[$class_name]))
            {
                // We did not load this class, denies this access.
                throw new Tequila_NoSuchClass($class_name);
            }
        }
        elseif ($this->_classLoader->load($class_name) &&
            class_exists($class_name, false))
        {
            $this->_loadedClasses[$class_name] = true;
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
     * Evaluates a node of the syntax tree.
     *
     * The node can be either a value (scalar), a list (array), a
     * variable (Tequila_Parser_Variable object) or a command
     * (Tequila_Parser_Command object).
     *
     * Evalutation means that variables will be replaced by their
     * value and commands will be replaced by their result.
     *
     * @param mixed $node
     *
     * @return mixed The result of the evaluation.
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

        if (!($node instanceof Tequila_Parser_Command))
        {
            // This should not happen and denote a programming error.
            trigger_error(
                'invalid node type: '.gettype($node),
                E_USER_ERROR
            );
        }

        $class = $this->evaluate($node->class);
        $method = $this->evaluate($node->method);
        $args = $this->evaluate($node->args);

        return $this->execute($class, $method, $args);
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

    /**
     * Asks something to the user (i.e. prints a question and reads an
     * answer).
     *
     * @param string $prompt The question.
     *
     * @return string|false The string read or false if an error occured.
     */
    public function prompt($prompt)
    {
        $value = $this->_reader->read($this, $prompt);

        // To make the  log as close as possible as  the user screen, writes
        // the given data.
        if ( ($logger = $this->logger) )
        {
            $logger->write($value, false);
        }

        return $value;
    }

    /**
     * Asks something to the user until he gives a legal answer.
     *
     * @param string  $prompt          The question.
     * @param array   $legalValues     The list of legal answers.
     * @param boolean $caseInsensitive Whether the case of the answer
     *     does not matter.
     *
     * @return string|false The string read or false if an error occured.
     */
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
     * Sets a Tequila options.
     *
     * Tequila options are used to tweak the shell behaviour.
     *
     * Currently, the following options are available:
     * - include-dirs: a list of directories in which to look for
     *   modules;
     * - log-file: the file where to store the log;
     * - quote-strings: a truth value indicating whether a single
     *   string result should be quoted.
     *
     * @param string $name  The name of the option.
     * @param mixed  $value The new value of the option.
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
                $handle = fopen($value, 'a');
                if (!$handle)
                {
                    throw new Exception('failed to open '.$value);
                }
                $logger = new Tequila_Writer_Stream($handle);
                $logger->write(
                    PHP_EOL.'[New session for '.$this->user.'] '.
                    date('c').PHP_EOL.PHP_EOL,
                    false
                );
                $this->logger = $logger;
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
     * Defines the value of a Tequila variable.
     *
     * @param string $name The name of the variable.
     * @param mixed  $value The new value of the variable.
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
     * @param string $indent The current indentation level (should be
     *                       left empty and used only by this function
     *                       itself).
     *
     * @return string A user-friendly string representing the value.
     */
    public function prettyFormat($value, $indent = '')
    {
        $nextIndent = $indent . self::INDENT;

        if (is_array($value))
        {
            $str = 'array [' . count($value) . '] (' . PHP_EOL;
            foreach ($value as $key => $entry)
            {
                $str .=
                    $nextIndent . $this->prettyFormat($key) . ' => ' .
                    $this->prettyFormat($entry, $nextIndent) . ',' . PHP_EOL;
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
        $_loadedClasses = array(), // Used for security purposes.
        $_parser,
        $_quoteStrings = true,
        $_reader,
        $_user,
        $_writer;

    /**
     * Helper function for the configuration parsing which returns
     * values for variables.
     *
     * If the variable is “user”, it returns the name of the current
     * user. Else if the variable exists in the environment its value
     * is returned.
     *
     * @param  array  $matches
     * @return string
     */
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
