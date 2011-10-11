<?php
require_once __DIR__.'/../src/Sesshin/ClassLoader/ClassLoader.php';

use Sesshin\ClassLoader\ClassLoader;

$loader = new ClassLoader();
$loader->register();

require_once __DIR__.'/Sesshin/Tests/TestCase.php';
