<?php
/**
 * @author Julien Fontanet <julien.fontanet@isonoe.net>
 */

/**
 * @covers Tequila_Parser
 */
class Tequila_ParserTest extends PHPUnit_Framework_TestCase
{
	protected $object;

	protected function setUp()
	{
		$this->object = new Tequila_Parser();
	}

	////////////////////////////////////////

	public function testConstructor()
	{
		$this->assertInstanceOf('Tequila_Parser', $this->object);

		$this->assertTrue($this->object->is_complete);

		$this->assertEmpty($this->object->words);
	}

	//--------------------------------------

	public function getPropertyProvider()
	{
		return array(

			'is_complete' =>
			array('is_complete', true, null),

			'words' =>
			array('words', array(), null),

			'unknown_property' =>
			array('unknown_property', null, 'Tequila_Exception'),
		);
	}

	/**
	 * @dataProvider getPropertyProvider
	 *
	 * @param string      $name
	 * @param mixed       $value
	 * @param string|null $exception
	 */
	public function testGetProperty($name, $value, $exception)
	{
		if ($exception !== null)
		{
			$this->setExpectedException($exception);
		}

		$this->assertSame($value, $this->object->$name);
	}

	//--------------------------------------

	public function parseProvider()
	{
		return array(

			// Simple parsing.
			array(
				'really simple parsing',
				array('really', 'simple', 'parsing')
			),

			// Quoted string.
			array('a "quoted string"', array('a', 'quoted string')),

			// Quoted substring.
			array('a quote"d substring"', array('a', 'quoted substring')),

			// Escaped characters.
			array(
				'lots\\ of \\"escaped" characters\\"" \\\\',
				array('lots of', '"escaped characters"', '\\')
			),

			// Incomplete parsing due to a missing escaped character.
			array('incomplete entry 1 \\', null),

			// Incomplete parsing due to a not closed string.
			array('incomplete entry 2 "', null),

			// Multiple passes parsing.
			array(
				array('a\ multiple\\', ' passes"', ' parsing"'),
				'a multiple passes parsing'
			),
		);
	}

	/**
	 * Parses strings and checks the result is the one expected.
	 *
	 * @dataProvider parseProvider
	 */
	public function testParse($strings, $result)
	{
		$strings = (array) $strings;

		foreach ($strings as $string)
		{
			$this->object->parse($string);
		}

		if ($result === null)
		{
			$this->assertFalse($this->object->is_complete);
			return;
		}

		$result = (array) $result;

		$this->assertTrue($this->object->is_complete);
		$this->assertSame($result, $this->object->words);
	}

	//--------------------------------------

	public function testReset()
	{
		$this->object->parse('first_word "\\');

		$this->assertFalse($this->object->is_complete);
		$this->assertNotEmpty($this->object->words);

		$this->object->reset();

		$this->assertTrue($this->object->is_complete);
		$this->assertEmpty($this->object->words);
	}
}
