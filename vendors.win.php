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
 * Get all dependencies needed for Phraseanet (Windows Version)
 */
/* Set the variables gitDir and phpDir with a trailing slash if it is not set in Windows' %PATH%
 * For example :
 * $gitDir="c:/msysgit/bin/";
 * $phpDir="c:/php5310/"
 */
$gitDir = "";
$phpDir = "";


chdir(__DIR__);

set_time_limit(0);

$composer = __DIR__ . '/composer.phar';

if ( ! file_exists($composer)) {
    file_put_contents($composer, file_get_contents('http://getcomposer.org/installer'), LOCK_EX);
    system($phpDir . 'php ' . $composer . ' install --dev');
}

system($phpDir . 'php ' . $composer . ' self-update');
system($phpDir . 'php ' . $composer . ' install --dev');

system($gitDir . 'git submodule init');
system($gitDir . 'git submodule update');

$iterator = new RecursiveDirectoryIterator(__DIR__ . '/lib/vendor/');

foreach ($iterator as $file) {
    /* @var $file SplFileInfo */
    if ($file->isDir()) {
        $cmd = sprintf('cd %s && ' . $gitDir . 'git submodule init && ' . $gitDir . 'git submodule update', escapeshellarg($file->getPathname()));
        system($cmd);
    }
}
