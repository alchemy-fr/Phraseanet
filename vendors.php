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

$composer = __DIR__ . '/composer.phar';

if ( ! file_exists($composer)) {
    system('curl -s http://getcomposer.org/installer | php');
    system('chmod +x ' . $composer);
    system($composer . ' install');
}

if ( ! is_executable($composer)) {
    system('chmod +x ' . $composer);
}

system($composer . ' self-update');
system($composer . ' install');

system('git submodule init');
system('git submodule update');

$iterator = new RecursiveDirectoryIterator(__DIR__ . '/lib/vendor/');

foreach ($iterator as $file) {
    /* @var $file SplFileInfo */
    if ($file->isDir()) {
        $cmd = sprintf('cd %s && git submodule init && git submodule update', escapeshellarg($file->getPathname()));
        system($cmd);
    }
}
