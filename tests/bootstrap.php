<?php
/**
 * @author Julien Fontanet <julien.fontanet@isonoe.net>
 */

$dir = defined('__DIR__') ? __DIR__ : dirname(__FILE__);

set_include_path(implode(PATH_SEPARATOR, array(
	'/usr/share/php5/lib',
	'/usr/share/php',
	$dir.'/../libs/gallic/src',
)));
require 'Gallic.php';

Gallic::$include_dirs[] = $dir.'/../src';
Gallic::$include_dirs[] = $dir;

unset($dir);
