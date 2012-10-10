<?php
/**
 * @author Julien Fontanet <julien.fontanet@isonoe.net>
 */

$dir = defined('__DIR__') ? __DIR__ : dirname(__FILE__);

require($dir.'/../src/lib/Gallic.php');
Gallic::$include_dirs[] = $dir;

unset($dir);
