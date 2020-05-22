<?php

require __DIR__ . '/db.php';

define('DB_HOST', 'localhost');
define('DB_NAME', 'database');
define('DB_USER', 'user');
define('DB_PASS', 'pass');

$db = new DB(DB_HOST, DB_NAME, DB_USER, DB_PASS);