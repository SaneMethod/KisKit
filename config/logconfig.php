<?php
/**
 * Copyright (c) Christopher Keefer, 2014. All Rights Reserved.
 *
 * Configuration for ExceptHandler based logging - set EXCEPT_HANDLER in config.php to the string of the option
 * group to be used, or false to disable use of ExceptHandler.
 *
 * Error Levels: 100 => 'DEBUG', 200 => 'INFO', 250 => 'NOTICE', 300 => 'WARNING', 600 => 'ERROR',
 * 700 => 'CRITICAL', 800 => 'ALERT', 900 => 'EMERGENCY'.
 * Possible Error Outputs: 'stderr' => php standard error output (php://stderr) - usually for CLI;
 * 'file' => log to a file (date-stamped, stored in logPath);
 * 'html' => log as html output (using logTemplate, detail controlled by webTrace);
 * 'json' => log as json output (will override html).
 *
 * webTrace: true == display full details of errors, including stack trace.
 * false == display only simple error messages.
 */
return array(
    'development' => array(
        'logPath' => SERVER_ROOT . APP_DIR . 'logs/',
        'logTemplate' => 'error',
        'webTrace' => true,
        'stderr' => false,
        'file' => 100,
        'html' => 100,
        'json' => false
    ),
    'production' => array(
        'logPath' => SERVER_ROOT . APP_DIR . 'logs/',
        'logTemplate' => 'error',
        'webTrace' => false,
        'stderr' => false,
        'file' => 200,
        'html' => 600,
        'json' => false
    )
);