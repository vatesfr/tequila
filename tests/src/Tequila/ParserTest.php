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
	}

	//--------------------------------------

	public function parseProvider()
	{
		return array(

			'empty' =>
			array('', array()),

			'whitespaces' =>
			array(" \n\t\r", array()),

			'null' =>
			array('null', array(null)),

			'quoted string' =>
			array(
				'"quoted string with escaped characters \n\t\r\\\\\\""',
				array("quoted string with escaped characters \n\t\r\\\"")
			),

			'raw string' =>
			array(
				'%(raw string \n\t\r\\\\)',
				array('raw string \n\t\r\\\\')
			),

			'raw strings with various delimiters' =>
			array(
				'%(who) %[what] %{where} %<how> %|why|',
				array('who', 'what', 'where', 'how', 'why')
			),

			'alaphanumeric delimiter for raw string' =>
			array(
				'%Aalphanumeric characters cannot be used as delimitersA',
				false
			),

			'space delimiter for raw string' =>
			array(
				'% alphanumeric characters cannot be used as delimiters ',
				false
			),

			'naked string' =>
			array(
				'naked\ string\ \n\t\r\\\\\\"',
				array("naked string \n\t\r\\\"")
			),

			'non terminated quoted string' =>
			array('"invalid quoted string', false),

			'non terminated raw string' =>
			array('%(invalid raw string', false),
		);
	}

	/**
	 * Parses strings and checks the result is the one expected.
	 *
	 * @dataProvider parseProvider
	 */
	public function testParse($string, $result)
	{
		$this->assertSame(
			$result,
			$this->object->parse($string)
		);
	}
}
