<?php

/**
 * This class loader creates any demanded class.
 */
class MyClassLoader extends Tequila_ClassLoader
{
	public $base;

	public function load($class_name)
	{
		eval('class '.$class_name.
		     ($this->base !== null ? ' extends '.$this->base : '').
		     ' {}');

		return true;
	}
}

class MyLogger extends Tequila_Logger
{
	public $log = array();

	protected function _log($message, $level)
	{
		$this->log[] = array($message, $level);
	}
}

class MyReader extends Tequila_Reader
{
	public $data = array();

	public function read(Tequila $tequila)
	{
		if (empty($this->data))
		{
			return false;
		}

		return array_shift($this->data);
	}
}

class MyWriter extends Tequila_Writer
{
	public $data = array();

	public function write($message, $error)
	{
		$this->data[] = array($message, $error);
	}
}

////////////////////////////////////////

class ClassWithOneAvailableMethod
{
	// Methods starting with a “_” should be ignored.
	public function __construct() {}
	public function _public_method() {}

	// Protected and private methods should be ignored.
	protected function protected_method() {}
	protected function _protected_method() {}
	private function private_method() {}
	private function _private_method() {}

	// This method should be visible.
	public function public_method($mandatory, $optional = null)
	{
		return array($optional, $mandatory);
	}
}

class MyTequilaModule extends Tequila_Module
{
	public function __construct(Tequila $tequila)
	{
		parent::__construct($tequila);
	}

	public function return_string()
	{
		return 'I don\'t want one position, I want all positions!';
	}

	public function start()
	{
		$this->_tequila->start();
	}

	public function stop()
	{
		$this->_tequila->stop();
	}

	public function require_arguments($one, $two, $three)
	{}

	public function throw_exception()
	{
		throw new Exception('Four stones, four crates, zero stones... ZERO CRATES!!!');
	}
}

////////////////////////////////////////////////////////////////////////////////

/**
 * @covers Tequila
 * @covers Tequila_Module
 */
class TequilaTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Returns the name of the next class to be generated.
	 */
	public static function getNextClass()
	{
		return 'myClass'.(self::$_id++);
	}

	private static $_id = 0;

	//--------------------------------------

	protected $object;

	protected function setUp()
	{
		$this->object = new Tequila(
			new MyClassLoader(),
			new MyLogger(),
			new MyReader(),
			new MyWriter()
		);
	}

	////////////////////////////////////////

	public function testConstructor()
	{
		$this->assertInstanceOf('Tequila', $this->object);
	}

	//--------------------------------------

	public function testDefaultConstructor()
	{
		$o = new Tequila();

		$this->assertInstanceOf('Tequila_ClassLoader_Void', $o->class_loader);
		$this->assertInstanceOf('Tequila_Logger_Void', $o->logger);
		$this->assertInstanceOf('Tequila_Reader_Plain', $o->reader);
		$this->assertInstanceOf(
			'Tequila_Writer_'.(extension_loaded('readline') ?
			                   'Readline' :
			                   'Plain'),
			$o->writer
		);
	}

	//--------------------------------------

	public function getPropertyProvider()
	{
		return array(

			'class_loader' =>
			array('class_loader', true, new MyClassLoader(), null),

			'is_running' =>
			array('is_running', false, false, null),

			'logger' =>
			array('logger', true, new MyLogger(), null),

			'reader' =>
			array('reader', true, new MyReader(), null),

			'user' =>
			array('user', false, getenv('USER'), null),

			'writer' =>
			array('writer', true, new MyWriter(), null),

			'unknown_property' =>
			array('unknown_property', false, null, 'Tequila_Exception'),
		);
	}

	/**
	 * @dataProvider getPropertyProvider
	 *
	 * @param string      $name
	 * @param boolean     $set_value
	 * @param mixed       $value
	 * @param string|null $exception
	 */
	public function testGetProperty($name, $set_value, $value, $exception)
	{
		if ($set_value)
		{
			$this->object->$name = $value;
		}

		if ($exception !== null)
		{
			$this->setExpectedException($exception);
		}

		$this->assertSame($value, $this->object->$name);
	}

	//--------------------------------------

	public function setPropertyProvider()
	{
		return array(

			// The only correct value for $class_loader is a
			// Tequila_ClassLoader.
			array('class_loader', null,           false),
			array('class_loader', 1337,           false),
			array('class_loader', 'a string',     false),
			array('class_loader', true,           false),
			array('class_loader', new stdClass(), false),

			array('class_loader', new MyClassLoader(), true),

			// The only correct value for $logger is a Tequila_Logger.
			array('logger', null,           false),
			array('logger', 1337,           false),
			array('logger', 'a string',     false),
			array('logger', true,           false),
			array('logger', new stdClass(), false),

			array('logger', new MyLogger(), true),

			// The only correct value for $reader is a Tequila_Reader.
			array('reader', null,           false),
			array('reader', 1337,           false),
			array('reader', 'a string',     false),
			array('reader', true,           false),
			array('reader', new stdClass(), false),

			array('reader', new MyReader(), true),

			// The only correct value for $writer is a Tequila_Writer.
			array('writer', null,           false),
			array('writer', 1337,           false),
			array('writer', 'a string',     false),
			array('writer', true,           false),
			array('writer', new stdClass(), false),

			array('writer', new MyWriter(), true),

			// Other properties are not settable.
			array('is_running', true,        false),
			array('user',       'Mr. Smith', false),

			// Unknown property.
			array('unknown', true, false),
		);
	}

	/**
	 * @dataProvider setPropertyProvider
	 *
	 * @param string  $name
	 * @param mixed   $value
	 * @param boolean $valid Whether or not the given value is valid.
	 */
	public function testSetProperty($name, $value, $valid)
	{
		if (!$valid)
		{
			$this->setExpectedException('Tequila_Exception');
		}

		$this->object->$name = $value;
	}

	//--------------------------------------

	public function startProvider()
	{
		return array(

			'Unspecified class' =>
			array('', false),

			'Unspecified method' =>
			array(self::getNextClass(), false),

			'No such method' =>
			array(self::getNextClass().' inexistant_method', false),

			'Not enough arguments' =>
			array(self::getNextClass().' require_arguments', false),

			'MyTequilaModule return_string' =>
			array(self::getNextClass().' return_string', false),

			'MyTequilaModule start' =>
			array(self::getNextClass().' start', true),

			'MyTequilaModule throw_exception' =>
			array(self::getNextClass().' throw_exception', false),

			'Reading error' =>
			array(false, false),
		);
	}

	/**
	 * @dataProvider startProvider
	 *
	 * @param string  $command
	 * @param boolean $exception
	 */
	public function testStart($command, $exception)
	{
		$this->object->class_loader->base = 'MyTequilaModule';
		$this->object->reader->data[] = $command;

		// Makes sure Tequila stops.
		$this->object->reader->data[] = self::getNextClass().' stop';

		$this->assertEmpty($this->object->writer->data);

		if ($command === false)
		{
			// Reading error.
			$expected = array('', false);
		}
		elseif ($exception)
		{
			// The method will try to start Tequila.
			$expected = array('Tequila is already running', true);
		}
		else
		{
			try
			{
				$expected = array(
					(string) $this->object->executeCommand($command),
					false
				);
			}
			catch (Tequila_Exception $e)
			{
				$expected = array($e->getMessage(), true);
			}
			catch (Exception $e)
			{
				$expected = array(get_class($e).': '.$e->getMessage(), true);
			}
		}

		$this->object->start();

		// data[0] is the prompt.
		$result = $this->object->writer->data[1];
		$result[0] = rtrim($result[0], PHP_EOL);

		$this->assertSame($expected, $result);
	}

	//--------------------------------------

	public function testGetAvailableMethods()
	{
		$this->object->class_loader->base = 'ClassWithOneAvailableMethod';

		$methods = $this->object->getAvailableMethods(
			$this->object->getClass(self::getNextClass())
		);

		$this->assertSame(1, count($methods));
		$this->assertSame('public_method', $methods[0]);
	}

	//--------------------------------------

	public function getClassProvider()
	{
		$cl_no  = new Tequila_ClassLoader_Void(); // Always fails.
		$cl_yes = new MyClassLoader();            // Always succeed.

		$class_to_load  = self::getNextClass();
		$existing_class = self::getNextClass();

		$cl_yes->load($existing_class);

		return array(

			// The loading failed.
			array($cl_no, $class_to_load, false),

			// The loading succeeded.
			array($cl_yes, $class_to_load, true),

			// This class exists already but has not be loaded by Tequila.
			array($cl_no, $existing_class, false),
			array($cl_yes, $existing_class, false),
		);
	}

	/**
	 * @dataProvider getClassProvider
	 *
	 * @param Tequila_ClassLoader $class_loader
	 * @param string              $class_name
	 * @param boolean             $valid        Whether  or  not  the  class  is
	 *                                          retrieved correctly.
	 */
	public function testGetClass(Tequila_ClassLoader $class_loader, $class_name,
	                             $valid)
	{
		if (!$valid)
		{
			$this->setExpectedException('Tequila_NoSuchClass');
		}

		$this->object->class_loader = $class_loader;

		$class = $this->object->getClass($class_name);
		$this->assertSame($class_name, $class->getName());
	}

	//--------------------------------------

	public function getMethodProvider()
	{
		return array(

			// Non-existing method.
			array('method123', false),

			// Public method.
			array('public_method', true),

			// Public method starting with a “_”.
			array('_public_method', false),

			// Protected method.
			array('protected_method', false),
			array('_protected_method', false),

			// Private method.
			array('private_method', false),
			array('_private_method', false),
		);
	}

	/**
	 * @dataProvider getMethodProvider
	 *
	 * @param string  $method_name
	 * @param boolean $valid
	 */
	public function testGetMethod($method_name, $valid)
	{
		$this->object->class_loader->base = 'ClassWithOneAvailableMethod';

		if (!$valid)
		{
			$this->setExpectedException('Tequila_NoSuchMethod');
		}

		$method = $this->object->getMethod(
			$this->object->getClass(self::getNextClass()),
			$method_name
		);

		$this->assertSame($method_name, $method->getname());
	}

	//--------------------------------------

	public function executeCommandProvider()
	{
		$cl_no  = new Tequila_ClassLoader_Void(); // Always fails.
		$cl_yes = new MyClassLoader();            // Always succeed.
		$cl_yes->base = 'ClassWithOneAvailableMethod';

		return array(

			// Unspecified class (empty command).
			array($cl_yes, '', null, 'Tequila_UnspecifiedClass'),


			// Unspecified method.
			array($cl_yes, self::getNextClass(), null,
			      'Tequila_UnspecifiedMethod'),

			// No such class.
			array($cl_no, self::getNextClass().' public_method mandatory', null,
			      'Tequila_NoSuchClass'),

			// No such method.
			array($cl_yes, self::getNextClass().' inexistant_method mandatory',
			      null, 'Tequila_NoSuchMethod'),

			// Not enough arguments.
			array($cl_yes, self::getNextClass().' public_method', null,
			      'Tequila_NotEnoughArguments'),

			// Success without optional argument.
			array($cl_yes, self::getNextClass().' public_method mandatory',
			      array(null, 'mandatory'), null),

			// Success with optional argument.
			array($cl_yes, self::getNextClass().' public_method mandatory optional',
			      array('optional', 'mandatory'), null),
		);
	}

	/**
	 * @dataProvider executeCommandProvider
	 *
	 * @param Tequila_ClassLoader $class_loader
	 * @param string              $command
	 * @param mixed               $result
	 * @param string|null         $exception
	 */
	public function testExecuteCommand(Tequila_ClassLoader $class_loader,
	                                   $command, $result, $exception)
	{
		if ($exception !== null)
		{
			$this->setExpectedException($exception);
		}

		$this->object->class_loader = $class_loader;

		$this->assertSame($result, $this->object->executeCommand($command));
	}

	//--------------------------------------

	public function executeProvider()
	{
		$cl_no  = new Tequila_ClassLoader_Void(); // Always fails.
		$cl_yes = new MyClassLoader();            // Always succeed.
		$cl_yes->base = 'ClassWithOneAvailableMethod';

		return array(

			// No such class.
			array($cl_no, self::getNextClass(), 'public_method', 'mandatory',
			      null, 'Tequila_NoSuchClass'),

			// No such method.
			array($cl_yes, self::getNextClass(), 'inexistant_method',
			      'mandatory', null, 'Tequila_NoSuchMethod'),

			// Not enough arguments.
			array($cl_yes, self::getNextClass(), 'public_method', null, null,
			      'Tequila_NotEnoughArguments'),

			// Success without optional argument.
			array($cl_yes, self::getNextClass(), 'public_method', 'mandatory',
			      array(null, 'mandatory'), null),

			// Success with optional argument.
			array($cl_yes, self::getNextClass(), 'public_method',
			      array('mandatory', 'optional'), array('optional', 'mandatory'),
			      null),
		);
	}

	/**
	 * @dataProvider executeProvider
	 *
	 * @param Tequila_ClassLoader $class_loader
	 * @param string              $class_name
	 * @param string              $method_name
	 * @param array|string|null   $arguments
	 * @param mixed               $result
	 * @param string|null         $exception
	 */
	public function testExecute(Tequila_ClassLoader $class_loader, $class_name,
	                            $method_name, $arguments, $result, $exception)
	{
		if ($exception !== null)
		{
			$this->setExpectedException($exception);
		}

		$this->object->class_loader = $class_loader;

		$this->assertSame(
			$result,
			$this->object->execute($class_name, $method_name,
			                       (array) $arguments)
		);
	}

	//--------------------------------------

	public function testPrompt()
	{
		$prompt = 'Why did you judge me? Why did you judge me?';
		$answer = 'You killed innocent people!';

		$this->assertEmpty($this->object->writer->data);
		$this->object->reader->data[] = $answer;

		$this->assertSame($answer, $this->object->prompt($prompt));
		$this->assertSame(array($prompt, false), $this->object->writer->data[0]);
	}

	//--------------------------------------

	public function writeProvider()
	{
		return array(

			'normal' =>
			array('That boy is our last hope.', false),

			'error' =>
			array('No. There is another.', true),
		);
	}

	/**
	 * @dataProvider writeProvider
	 */
	public function testWrite($string, $error)
	{
		$logger = $this->object->logger;
		$writer = $this->object->writer;

		$this->assertEmpty($logger->log);
		$this->assertEmpty($writer->data);

		$this->object->write($string, $error);

		$this->assertSame(
			array(
				$string,
				$error ? Tequila_Logger::WARNING : Tequila_Logger::NOTICE
			),
			$logger->log[0]
		);
		$this->assertSame(array($string, $error), $writer->data[0]);
	}

	//--------------------------------------

	public function parseConfigEntryProvider()
	{
		$user = getenv('USER');
		$home = getenv('HOME');

		return array(

			'Simple entry' =>
			array('This is a simple entry', 'This is a simple entry'),

			'@USER@ variable' =>
			array('My name is @USER@', 'My name is '.$user),

			'Environment variable' =>
			array('My home is @HOME@', 'My home is '.$home),

			'Inexistant environment variable' =>
			array('@INEXISTANT_VARIABLE@', '@INEXISTANT_VARIABLE@'),

			'Malformed variable' =>
			array('@user@', '@user@'),

			'Array' =>
			array(
				array('@USER@', '@HOME@'),
				array($user,    $home)
			),
		);
	}

	/**
	 * @dataProvider parseConfigEntryProvider
	 *
	 * @param string $origin
	 * @param string $result
	 */
	public function testParseConfigEntry($origin, $result)
	{
		$this->assertSame($result, $this->object->parseConfigEntry($origin));
	}
}
