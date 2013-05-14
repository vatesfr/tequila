<?php

/**
 * This writer simply writes the data on the writers output if $error is false,
 * or on the writers error otherwise.
 *
 * Due to the implementation, it is currently non testable.
 *
 * @author Julien Fontanet <julien.fontanet@isonoe.net>
 *
 * @codeCoverageIgnore
 */
final class Tequila_Writer_Aggregate extends Tequila_Writer implements
	ArrayAccess,
	Countable,
	IteratorAggregate
{
	/**
	 * Construct a new aggregate writer.
	 *
	 * @param Tequila_Writer[] $writers
	 */
	public function __construct(array $writers = array())
	{
		$this->_writers = $writers;
	}

	/**
	 * @see parent::write()
	 */
	public function write($string, $error)
	{
		foreach ($this->_writers as $writer)
		{
			$writer->write($string, $error);
		}
	}

	/**
	 * @var Tequila_Writer[]
	 */
	private $_writers;

	//--------------------------------------
	// ArrayAccess
	//--------------------------------------

	public function offsetGet($offset)
	{
		return $this->_writers[$offset];
	}

	public function offsetExists($offset)
	{
		return isset($this->_writers[$offset]);
	}

	public function offsetSet($offset, $value)
	{
		if ( !($value instanceof Tequila_Writer) )
		{
			trigger_error(
				get_class($this).' can only contains instances of Tequila_Writer',
				E_USER_ERROR
			);
		}

		if (null === $offset)
		{
			$this->_writers[] = $value;
		}
		else
		{
			$this->_writers[$offset] = $value;
		}
	}

	public function offsetUnset($offset)
	{
		unset($this->_writers[$offset]);
	}

	//--------------------------------------
	// Countable
	//--------------------------------------

	public function count()
	{
		return count($this->_writers);
	}

	//--------------------------------------
	// IteratorAggregate
	//--------------------------------------

	public function getIterator()
	{
		return new ArrayIterator($this->_writers);
	}
}
