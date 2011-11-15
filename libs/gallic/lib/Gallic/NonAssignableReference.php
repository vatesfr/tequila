<?php
/**
 * This file is a part of Gallic.
 *
 * Gallic is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Gallic is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Gallic. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Julien Fontanet <julien.fontanet@isonoe.net>
 * @license http://www.gnu.org/licenses/gpl-3.0-standalone.html GPLv3
 *
 * @package Gallic
 */

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
