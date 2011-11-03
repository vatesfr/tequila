<?php

/**
 * This  class permits  to manipulate  non-assignable references  to  objects or
 * arrays.
 *
 * <code>
 *   function &f()
 *   {
 *     static $v = array();
 *     return $v;
 *   }
 *   $v = &f();
 *   $v[0] = 4; // f static is variable is modified.
 *   $v = 3;    // f static variable is assigned.
 *
 *   function &g()
 *   {
 *     static $v = array();
 *     return new Gallic_NonAssignableReference($v);
 *   }
 *   $v = &g();
 *   $v[0] = 4; // g static is variable is modified.
 *   $v = 3;    // g static variable is NOT assigned!
 * </code>
 *
 * Please  note  that  this  class  implements the  ArrayAccess,  Countable  and
 * IteratorAggregate  interfaces  and, as  a  consequence,  might  not call  the
 * related method  correctly on the refeferenced  object if it  does not respect
 * these interfaces itself.
 *
 * Also, some magic methods are not forwarded:
 * - __sleep()
 * - __wakeup()
 * - __set_state()
 */
class Gallic_NonAssignableReference implements ArrayAccess, Countable, IteratorAggregate
{
	public function __construct(&$object)
	{
		$this->_ref = &$object;
	}

	public function __call($name, $arguments)
	{
		return call_user_func_args(array($this->_ref, $name), $arguments);
	}

	public function __clone()
	{
		return clone $this->_ref;
	}

	public function __get($name)
	{
		return $this->_ref->$name;
	}

	public function __invoke()
	{
		$args = func_get_args();

		return call_user_func_args($this->_ref, $args);
	}

	public function __isset($name)
	{
		return isset($this->_ref->$name);
	}

	public function __set($name, $value)
	{
		$this->_ref->$name = $value;
	}

	public function __toString()
	{
		return ((string) $this->_ref);
	}

	public function __unset($name)
	{
		unset($this->_ref->$name);
	}

	////////////////////////////////////////
	// Countable

	public function count()
	{
		return count($this->_ref);
	}

	////////////////////////////////////////
	// IteratorAggregate

	public function getIterator()
	{
		if (is_array($this->_ref))
		{
			return new ArrayIterator($this->_ref);
		}

		return $this->_ref->getIterator();
	}

	////////////////////////////////////////
	// ArrayAccess

	public function offsetExists($name)
	{
		return isset($this->_ref[$name]);
	}

	public function offsetGet($name)
	{
		return $this->_ref[$name];
	}

	public function offsetSet($name, $value)
	{
		$this->_ref[$name] = $value;
	}

	public function offsetUnset($name)
	{
		unset($this->_ref[$name]);
	}

	private $_ref;
}
