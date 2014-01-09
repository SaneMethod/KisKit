<?php
/**
 * Copyright (c) Christopher Keefer, 2013. All Rights Reserved.
 *
 * Database configuration - this file should be customized to represent the details of your database setup.
 * Can be left blank if you're not going to use a database, or planning to interact with a database through
 * something other than PDO.
 */
$active_group = 'default';

$db['default']['hostname'] = '127.0.0.1';
$db['default']['driver'] = 'mysql';
$db['default']['port'] = 3306; // Number to specify port, null for default
$db['default']['username'] = 'cedb';
$db['default']['password'] = 'ceck93z%!NWzy3R^@!5w8>h';
$db['default']['database'] = 'cedb';

$db['production']['hostname'] = '127.0.0.1';
$db['production']['driver'] = 'pgsql';
$db['production']['port'] = null;
$db['production']['username'] = '';
$db['production']['password'] = '';
$db['production']['database'] = '';