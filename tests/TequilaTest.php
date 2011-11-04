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
	public function _method() {}

	// Protected and private methods should be ignored.
	protected function protected_method() {}
	protected function _protected_method() {}
	private function private_method() {}
	private function _private_method() {}

	// This method should be visible.
	public function public_method() {}
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

	public function getClassProvider()
	{
		$class_loader_1 = new Tequila_ClassLoader_Void(); // Always fails.
		$class_loader_2 = new MyClassLoader();            // Always succeed.

		$class_to_load  = self::getNextClass();
		$existing_class = self::getNextClass();

		$class_loader_2->load($existing_class);

		return array(

			// The loading failed.
			array($class_loader_1, $class_to_load, false),

			// The loading succeeded.
			array($class_loader_2, $class_to_load, true),

			// This class exists already but has not be loaded by Tequila.
			array($class_loader_1, $existing_class, false),
			array($class_loader_2, $existing_class, false),
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
	public function testGetClass($class_loader, $class_name, $valid)
	{
		if (!$valid)
		{
			$this->setExpectedException('Tequila_NoSuchClass');
		}

		$this->object->class_loader = $class_loader;

		$class = $this->object->getClass($class_name);
		$this->assertEquals($class_name, $class->getName());
	}

	//--------------------------------------

	public function testGetAvailableMethod()
	{
		$this->object->class_loader = new MyClassLoader();
		$this->object->class_loader->base = 'ClassWithOneAvailableMethod';

		$methods = $this->object->getAvailableMethods(
			$this->object->getClass(self::getNextClass())
		);

		$this->assertEquals(1, count($methods));
		$this->assertEquals('public_method', $methods[0]);
	}

	//--------------------------------------

	public function getMethodProvider()
	{
		return array(

			// Existing method.
			array('public_method', true),

			// Non-existing method.
			array('method123', false),
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

		$this->assertEquals($method_name, $method->getname());
	}
}
