<?php
/**
 * Copyright (c) Christopher Keefer 2013, All Rights Reserved.
 *
 * KisKit offers a simple, stripped-down MVC to offer some organization for projects that
 * don't need the features (and size or complexity) offered by more complete frameworks like
 * CodeIgniter or Yii.
 *
 * Index defines details of our server and routing, kicks off to default or requested controller.
 */

// Define server root, replace backslashes, ensure there's a trailing slash
define('SERVER_ROOT', rtrim(str_replace("\\", "/", dirname(__FILE__)), '/').'/');

// Require configuration details
require_once(SERVER_ROOT . 'config/config.php');

// Define base url - derive it from server values, ensure there's a trailing slash
define('BASE_URL',
    ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != "off") ? 'https' : 'http')
    .'://'.$_SERVER['HTTP_HOST'] .
    rtrim(substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'],'/')), '/').'/'
);


// Require Controller and Model
require_once(SERVER_ROOT . SYS_DIR . 'core/controller.php');
require_once(SERVER_ROOT . SYS_DIR . 'core/model.php');

// Include composer-generated autoloader and PSR-3 conforming exceptHandler, if available
if ((include(SERVER_ROOT . 'vendor/autoload.php')) && USE_EXCEPT_HANDLER)
{
    $logger = new sanemethod\kiskit\system\core\ExceptHandler();
}

// Require router and instantiate
require_once(SERVER_ROOT . SYS_DIR . 'core/request.php');
require_once(SERVER_ROOT . SYS_DIR . 'core/router.php');

use sanemethod\kiskit\system\core\Router;
$router = new Router(array('logger'=>$logger));