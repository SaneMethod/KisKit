<?php
/**
 * Copyright (c) Christopher Keefer, 2013. All Rights Reserved.
 *
 * Database configuration - this file should be customized to represent the details of your database setup.
 * Can be left blank if you're not going to use a database, or planning to interact with a database through
 * something other than PDO.
 */
return array(
    'development' => array(
        'hostname' => '127.0.0.1',
        'driver' => 'mysql',
        // Number to specify port, null to use default port
        'port' => 3306,
        'username' => 'cedb',
        'password' => 'ceck93z%!NWzy3R^@!5w8>h',
        'database' => 'cedb'
    ),
    'production' => array(
        'hostname' => '127.0.0.1',
        'driver' => 'pqsql',
        // Number to specify port, null to use default port
        'port' => null,
        'username' => '',
        'password' => '',
        'database' => ''
    )
);