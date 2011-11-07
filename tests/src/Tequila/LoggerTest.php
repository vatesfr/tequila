<?php

class MyLogger2 extends Tequila_Logger
{
	public $data = array();

	public function _log($message, $level)
	{
		$this->data[] = array($message, $level);
	}
}

////////////////////////////////////////////////////////////////////////////////

/**
 * @covers Tequila_Logger
 */
class Tequila_LoggerTest extends PHPUnit_Framework_TestCase
{
	protected static $levels;

	public static function cartesianProduct()
	{
		$entries = func_get_args();
		$result = (array) array_shift($entries);

		foreach ($entries as $entry)
		{
			$tmp = array();

			$entry = (array) $entry;

			foreach ($result as $a)
			{
				foreach ($entry as $value)
				{
					$tmp[] = array_merge((array) $a, (array) $value);;
				}
			}

			$result = $tmp;
		}

		return $result;
	}

	//--------------------------------------

	public static function init()
	{
		if (self::$levels !== null)
		{
			return;
		}

		$class = new ReflectionClass('Tequila_Logger');

		self::$levels = $class->getConstants();
	}

	public function setUp()
	{
		self::init();

		$this->object = new MyLogger2();
	}

	protected $object;

	////////////////////////////////////////

	public function testGetLevelName()
	{
		foreach (self::$levels as $name => $value)
		{
			$this->assertSame($name,
			                  Tequila_Logger::getLevelName($value));
		}

		// Other values returns ''.
		foreach (array(5, '', false, null) as $value)
		{
			$this->assertSame('', Tequila_Logger::getLevelName($value));
		}
	}

	//--------------------------------------

	public function _logProvider()
	{
		// Makes sure self::$levels is correctly initialized.
		self::init();

		$tmp = array();
		foreach (self::$levels as $level)
		{
			$tmp[] = array(null, $level);
		}

		$configurations = call_user_func_array('self::cartesianProduct', $tmp);

		$data = array();

		foreach (self::$levels as $level)
		{
			foreach ($configurations as $entries)
			{
				$enabled       = false;
				$configuration = 0;

				foreach ($entries as $entry)
				{
					if (!$enabled && ($entry === $level))
					{
						$enabled = true;
					}
					$configuration |= $entry;
				}

				$data[] = array($enabled, $configuration, $level);
			}
		}

		return $data;
	}

	/**
	 * @dataProvider _logProvider
	 *
	 * @param boolean $enabled       Whether or not the log should be taken into
	 *                               consideration.
	 * @param integer $configuration The current configuration of the logger.
	 * @param integer $level         The level of the message.
	 */
	public function test_log($enabled, $configuration, $level)
	{
		$string = 'Stop trying to hit me and hit me!';

		$this->object->level = $configuration;

		$this->assertEmpty($this->object->data);

		$this->object->log($string, $level);

		if ($enabled)
		{
			$this->assertSame(array($string, $level), $this->object->data[0]);
		}
		else
		{
			$this->assertEmpty($this->object->data);
		}
	}
}
