<?php

$autoloader = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloader) && (!$loader = include $autoloader)) {
    die(
        'You must set up the project dependencies, run the followings commands:' . PHP_EOL
        . 'curl -s http://getcomposer.org/installer | php' . PHP_EOL
        . 'php composer.phar install' . PHP_EOL
    );
}

return $loader;
