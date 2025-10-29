<?php

return [
    'driver' => 'pgsql',
    'host' => 'db',
    'port' => '5432',
    'database' => 'nimonspedia_db',
    'username' => 'nimonspedia_user',
    'password' => 'nimonspedia_password',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];