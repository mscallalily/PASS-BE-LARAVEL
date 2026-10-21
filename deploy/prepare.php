<?php

// Also runs during Composer installation, before Laravel package discovery/build caches.
foreach (['bootstrap/cache', 'resources/views', 'storage/app/private', 'storage/app/public', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs'] as $directory) {
    $path = dirname(__DIR__).'/'.$directory;
    if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
        throw new RuntimeException('Unable to prepare Laravel writable directory: '.$directory);
    }
}
