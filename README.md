Sesshin
=======

PHP advanced session management.

Requirements
------------

* PHP 5.3

Usage
-----

    <?php
    require_once __DIR__.'/src/Sesshin/ClassLoader/ClassLoader.php';

    use Sesshin\ClassLoader\ClassLoader;
    use Sesshin\Session\Session;

    $loader = new ClassLoader();
    $loader->register();

    $session = new Session();
