#!/usr/bin/env php
<?php

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Get all dependencies needed for Phraseanet
 */
chdir(__DIR__);

set_time_limit(0);

$bower = 'bower';
exec($bower, $output, $code);

if (0 !== $code) {
    exit('bower required to install vendors');
}

system(sprintf('%s install', $bower));

if (0 !== $code) {
    exit('Failed to install bower dependencies');
}

$composer = __DIR__ . '/composer.phar';

exec('composer', $output, $code);

if (0 !== $code && ! file_exists($composer)) {
    system('curl -s http://getcomposer.org/installer | php');
    system('chmod +x ' . $composer);

    if (isset($argv[1]) && $argv[1] == '--no-dev') {
        system($composer . ' install --optimize-autoloader --no-dev');
    } else {
        system($composer . ' install --dev --optimize-autoloader');
    }
}

if (0 === $code) {
    $composer = 'composer';
} elseif ( ! is_executable($composer)) {
    system('chmod +x ' . $composer);
}

system($composer . ' self-update');

if (isset($argv[1]) && $argv[1] == '--no-dev') {
    system($composer . ' install --optimize-autoloader --no-dev');
} else {
    system($composer . ' install --dev --optimize-autoloader');
}

system('bin/setup less:compile');

