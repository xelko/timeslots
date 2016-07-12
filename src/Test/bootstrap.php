<?php

spl_autoload_register(function ($class) {
    $paths = [
      __DIR__ . '/',
      __DIR__ . '/../',
    ];

    foreach ($paths as $path) {
        $filename = "{$path}{$class}.php";
        if (is_file($filename)) {
            include_once $filename;
            break;
        }
    }
});
