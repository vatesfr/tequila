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

class MyLogger extends Tequila_Logger
{
	public $log = array();

	protected function _log($message, $level)
	{
		$this->log[] = array($message, $level);
	}
}

////////////////////////////////////////////////////////////////////////////////

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
		$this->object = Tequila::create();
	}

	////////////////////////////////////////

	public function testCreate()
	{
		$this->assertInstanceOf(
			extension_loaded('readline') ? 'Tequila_Readline' : 'Tequila_Plain',
			$this->object
		);
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

			array('class_loader', new Tequila_ClassLoader_Void(), true),

			// The only correct value for $logger is a Tequila_Logger.
			array('logger', null,           false),
			array('logger', 1337,           false),
			array('logger', 'a string',     false),
			array('logger', true,           false),
			array('logger', new stdClass(), false),

			array('logger', new Tequila_Logger_Void(), true),

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

	public function testGetAvailableMethods()
	{
		$this->object->class_loader = new MyClassLoader();
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
		$this->object->class_loader = new MyClassLoader();
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


	/**
	 * @todo Figure out how to test fwrite to STDOUT or STDERR.
	 */
	/* public function testWrite() */
	/* { */
	/* 	$string = 'This is a test string.'; */
	/* 	$this->expectOutputString($string); */

	/* 	$logger = new MyLogger(); */
	/* 	$this->object->logger = $logger; */

	/* 	$this->assertEmpty($logger->log); */

	/* 	$this->object->write($string); */

	/* 	$this->assertNotEmpty($logger->log); */
	/* 	$this->assertSame(array($string, Tequila_Logger::NOTICE), */
	/* 	                  $logger->log[0]); */
	/* } */

	//--------------------------------------

	public function parseConfigEntryProvider()
	{
		return array(

			// Simple entry.
			array('This is a simple entry', 'This is a simple entry'),

			// @USER@ variable.
			array('My name is @USER@', 'My name is '.getenv('USER')),

			// Environment variable.
			array('My home is @HOME@', 'My home is '.getenv('HOME')),

			// Inexistant environment variable.
			array('@INEXISTANT_VARIABLE@', '@INEXISTANT_VARIABLE@'),

			// Malformed variable.
			array('@user@', '@user@'),
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

	//--------------------------------------

	public function testAddToHistory()
	{
		$string = 'These aren\'t the droids you\'re looking for.';

		$this->assertEmpty($this->object->history);

		$this->object->addToHistory($string);

		$this->assertNotEmpty($this->object->history);
		$this->assertSame($string, $this->object->history[0]);
	}

	//--------------------------------------

	public function testClearHistory()
	{
		$string = 'These aren\'t the droids we\'re looking for.';

		$this->object->addToHistory($string);
		$this->object->clearHistory();

		$this->assertEmpty($this->object->history);
	}
}
