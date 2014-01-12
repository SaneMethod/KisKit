<?php
/**
 * Copyright (c) Christopher Keefer, 2013. All Rights Reserved.
 *
 * This file should be customized to represent the appropriate details of your setup for all elements, except
 * database configuration (see dbconfig.php) and log configuration (see logconfig.php).
 */
/**
 * Path (relative to root) where the application specific files (controller, models, static, etc.) can be found.
 */
define('APP_DIR', 'app/');
/**
 * Path (relative to root) where the system files (core, helpers, etc.) can be found.
 */
define('SYS_DIR', 'system/');
/**
 * The default controller when no controller is specified in the url.
 */
define('DEFAULT_CONTROLLER', 'home');
/**
 * String identifying the database configuration array to use.
 */
define('DB_GROUP', 'development');
/**
 * Either a string representing the active log option group, or false to disable ExceptHandler use.
 */
define('EXCEPT_HANDLER', 'development');
/**
 * Path to use to store uploaded files.
 */
define('UPLOAD_FILE_PATH', SERVER_ROOT . APP_DIR . 'files/');