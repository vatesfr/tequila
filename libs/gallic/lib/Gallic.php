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
 * This  is  not  really  a  part  of  Tequila, there  are  no  unit  tests,  as
 * a consequence we ignore the code coverage analysis.
 */
// @codeCoverageIgnoreStart

final class Gallic
{
	const VERSION = '0.2.0';

	public static $include_dirs = array();

	/**
	 * Defines a constant if necessary.
	 *
	 * @param type $name
	 * @param type $value
	 */
	public static function define_default($name, $value)
	{
		if (!defined($name))
		{
			define($name, $value);
		}
	}

	private function __construct() {}

	private function __clone() {}
}
Gallic::$include_dirs[] = defined('__DIR__') ? __DIR__ : dirname(__FILE__);

////////////////////////////////////////////////////////////////////////////////

final class Gallic_Path
{
	public static function is_absolute($path)
	{
		return ($path[0] === '/');
	}

	public static function join()
	{
		// Prior  to PHP  5.3, func_get_args()  cannot  be used  directly as  an
		// argument.
		$args = func_get_args();

		return implode(DIRECTORY_SEPARATOR, $args);
	}


	/**
	 * Normalizes a path, which means, removes every '//', '.' and '..'.
	 *
	 * @param string $path The path to normalize.
	 *
	 * @return string The path normalized.
	 */
	public static function normalize($path)
	{
		if ($path === '')
		{
			return '.';
		}

		$path = explode(DIRECTORY_SEPARATOR, $path);

		$out = array($path[0]);
		array_shift($path);

		foreach ($path as $component)
		{
			if (($component === '') || ($component === '.'))
			{
				continue;
			}

			if (($component === '..') && (($prev = end($out)) !== '..'))
			{
				if ($prev !== '')
				{
					array_pop($out);
				}
				continue;
			}

			array_push($out, $component);
		}

		$n = count($out);
		if ($n === 0)
		{
			return '.';
		}

		if ($n === 1)
		{
			if ($out[0] === '')
			{
				return '/';
			}
			return $out[0];
		}

		return implode(DIRECTORY_SEPARATOR, $out);
	}

	private function __construct() {}

	private function __clone() {}
}

////////////////////////////////////////////////////////////////////////////////

final class Gallic_File
{
	public static function find($path, $predicate = null, $dirs = null)
	{
		if (is_null($predicate))
		{
			$predicate = 'is_readable';
		}

		$dirs = $dirs !== null ? (array) $dirs : Gallic::$include_dirs;

		if (Gallic_Path::is_absolute($path))
		{
			return (call_user_func($predicate, $path) ? $path : false);
		}

		foreach ($dirs as $dir)
		{
			$full_path = Gallic_Path::join($dir, $path);
			if (call_user_func($predicate, $full_path))
			{
				return $full_path;
			}
		}

		return false;
	}

	private function __construct() {}

	private function __clone() {}
}

////////////////////////////////////////////////////////////////////////////////

final class Gallic_Loader
{
	/**
	 * Interface for spl_autoload().
	 *
	 * @param string $classname
	 */
	public static function autoload($classname)
	{
		return (self::loadClass($classname) &&
		        (class_exists($classname, false) ||
		         interface_exists($classname, false)));
	}

	public static function loadClass($classname, $dirs = null)
	{
		$path = str_replace('_', DIRECTORY_SEPARATOR, $classname).'.php';
		return self::loadFile($path, $dirs);
	}

	public static function loadFile($path, $dirs = null)
	{
		$path = Gallic_File::find($path, 'is_readable', $dirs);
		if ($path === false)
		{
			return false;
		}

		include $path;

		return true;
	}

	private function __construct() {}

	private function __clone() {}
}
spl_autoload_register(array('Gallic_Loader', 'autoload'));

// @codeCoverageIgnoreEnd