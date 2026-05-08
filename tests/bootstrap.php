<?php

declare(strict_types=1);

$cachedConfigurationPath = __DIR__.'/../bootstrap/cache/config.php';

if (is_file($cachedConfigurationPath)) {
    unlink($cachedConfigurationPath);
}

require __DIR__.'/../vendor/autoload.php';
