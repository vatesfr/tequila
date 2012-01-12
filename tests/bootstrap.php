<?php
/**
 * @author Julien Fontanet <julien.fontanet@isonoe.net>
 */

$dir = defined('__DIR__') ? __DIR__ : dirname(__FILE__);

set_include_path(implode(PATH_SEPARATOR, array(
	$dir.'/../libs/gallic/src',
	'/usr/share/php5/lib',
	'/usr/share/php',
)));
require 'Gallic.php';

Gallic::$include_dirs[] = $dir.'/../src';
Gallic::$include_dirs[] = $dir;

unset($dir);
