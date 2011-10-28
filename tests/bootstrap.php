<?php

$dir = dirname(__FILE__).'/../src';

require $dir.'/Gallic.php';
Gallic::$include_dirs[] = $dir;

unset($dir);
