<?php

$settings = [
    'debug' => true,
    'base' => 'http://localhost:8084/',
    /**
     * Database configuration
     */
    'db' => [
        'adapter' => 'mysql',
        'host' => '',
        'username' => '',
        'password' => '',
        'name' => 'phramework_jsonapi',
        'port' => 3306
    ],
];

//Overwrite setting if localsettings.php exists
if (file_exists(__DIR__ .'/localsettings.php')) {
    include(__DIR__ . '/localsettings.php');
}

return $settings;
