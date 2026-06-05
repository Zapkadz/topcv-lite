<?php

/**
 * Load Composer autoload khi vendor/ đã được cài (composer install).
 */
$autoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}
