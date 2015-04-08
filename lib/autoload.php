<?php

return call_user_func(function () {
    $loader = require __DIR__ . '/../vendor/autoload.php';
    if (file_exists(__DIR__ . '/../plugins/autoload.php')) {
        require __DIR__ . '/../plugins/autoload.php';
    }

    return $loader;
});
