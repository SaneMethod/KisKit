<?php
/**
 * Entry point for phpunit tests, performing initial autoloader include and other environment setup.
 */
define('SERVER_ROOT', rtrim(str_replace("\\", "/", realpath(dirname(__FILE__).'/../')), '/').'/');

// Require configuration details and autoloader
require_once(SERVER_ROOT . 'config/config.php');
require_once(SERVER_ROOT . 'vendor/autoload.php');
require_once(SERVER_ROOT . 'tests/mock.php');