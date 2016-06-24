<?php

$web_root = "/src";

$currently_updating_file = '/tmp/magento-start-in-progress';

$envFile = "$web_root/app/etc/env.php";


// I guess we never come to this stage ( currently updating ) .
// Since php-fpm starts once the update routine is finished. 502 will happen until php-fpm is started.
if (file_exists($currently_updating_file)) {
    header("HTTP/1.1 500 Internal Server Error");
    echo "Status: Currently in update progress\n";
    exit;

}


if (!file_exists($envFile)) {
    header("HTTP/1.1 500 Internal Server Error");
    echo "Status: Magento 2 enviroment (env.php) file : $envFile could not be found.";
    exit;
}


// require magento 2's env php.
$magentoEnv = require("$web_root/app/etc/env.php");



// Lets check
// 1. The database ( its RDS )
// 2. The memcache ( if set )
// 3. Other things to test if the environment is setup correctly or healthy.


header("HTTP/1.1 200 OK");
echo "Status: OK";