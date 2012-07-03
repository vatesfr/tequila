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

	public function successProvider()
	{
		return array(

			'null' =>
			array(
				'class method null',
				'class',
				'method',
				array(null)
			),

			'quoted string' =>
			array(
				'class method "quoted string with escaped characters \n\t\r\\\\\\""',
				'class',
				'method',
				array("quoted string with escaped characters \n\t\r\\\"")
			),

			'raw string' =>
			array(
				'class method %(raw string \n\t\r\\\\)',
				'class',
				'method',
				array('raw string \n\t\r\\\\')
			),

			'raw strings with various delimiters' =>
			array(
				'class method %(who) %[what] %{where} %<how> %|why|',
				'class',
				'method',
				array('who', 'what', 'where', 'how', 'why')
			),

			'naked string' =>
			array(
				'class method naked\ string\ \n\t\r\\\\\\"',
				'class',
				'method',
				array("naked string \n\t\r\\\"")
			),

			'one character naked string' =>
			array(
				'class method _',
				'class',
				'method',
				array('_')
			),
		);
	}

	/**
	 * @dataProvider successProvider
	 */
	public function testSuccess($string, $class, $method, $args)
	{

		$this->assertEquals(
			new Tequila_Parser_Command($class, $method, $args),
			$this->object->parse($string)
		);
	}

	//--------------------------------------

	public function failureProvider()
	{
		return array(

			'missing class' =>
			array(
				'',
				'Tequila_UnspecifiedClass'
			),

			'missing method' =>
			array(
				'class',
				'Tequila_UnspecifiedMethod'
			),

			'alaphanumeric delimiter for raw string' =>
			array(
				'class method %Aalphanumeric characters cannot be used as delimitersA',
				'Tequila_Exception'
			),

			'space delimiter for raw string' =>
			array(
				'class method % alphanumeric characters cannot be used as delimiters ',
				'Tequila_Exception'
			),

			'non terminated quoted string' =>
			array(
				'class method "invalid quoted string',
				'Tequila_Exception'
			),

			'non terminated raw string' =>
			array(
				'class method %(invalid raw string',
				'Tequila_Exception'
			),
		);
	}

	/**
	 * @dataProvider failureProvider
	 */
	public function testFailer($string, $exception)
	{
		$this->setExpectedException($exception);

		$this->object->parse($string);
	}
}
