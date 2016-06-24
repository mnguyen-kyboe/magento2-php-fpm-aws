<?php

$currently_updating_file = '/tmp/magento-start-in-progress';


if (file_get_contents($currently_updating_file)) {
    http_response_code (500);
    echo "Status: Currently in update progress\n";
} else {
    http_response_code(200);
    echo "Status: OK";
}