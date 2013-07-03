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
$node = 'node';
$recess = 'recess';
$uglifyjs = 'uglifyjs';
$npm = 'npm';

// Test if node exists
exec(sprintf('%s -v', $node), $output, $code);

if (0 !== $code) {
    echo sprintf('%s is required to install vendors', $node);
    exit(1);
}

// Test if npm exists
exec(sprintf('%s -v', $npm), $output, $code);

if (0 !== $code) {
    echo sprintf('%s is required to install vendors', $npm),
    exit(1);
}

// Test if bower exists else install it
exec(sprintf('%s -v', $bower), $output, $code);

if (0 !== $code) {
    exec(sprintf('sudo %s install %s -g', $npm, $bower), $output, $code);

    if (0 !== $code) {
        echo sprintf('Failed to install %s', $bower);
        exit(1);
    }
}

// Tests if recess exists else install it
exec(sprintf('%s -v', $recess), $output, $code);

if (0 !== $code) {
    exec(sprintf('sudo %s install %s -g', $npm, $recess), $output, $code);

    if (0 !== $code) {
        echo sprintf('Failed to install %s', $recess);
        exit(1);
    }
}

// Tests if recess exists else install it
exec('uglifyjs --version', $output, $code);

if (0 !== $code) {
    exec(sprintf('sudo %s install uglify-js -g', $npm), $output, $code);

    if (0 !== $code) {
        echo 'Failed to install uglifyjs';
        exit(1);
    }
}

// Remove previous assets
$assetDir = __DIR__. '/www/assets';
$code = 0;
if (is_dir($assetDir)) {
    system('rm -rf ' . escapeshellarg($assetDir), $code);
}

if (0 !== $code) {
    echo sprintf('Attention, failed to remove previous %s dependencies in %s', $bower, $assetDir);
    echo "\n";
}

// Clean bower cache
system(sprintf('%s cache-clean', $bower), $code);

if (0 !== $code) {
    echo sprintf('Attention, failed to clean %s cache', $bower);
    echo "\n";
}

// Install asset dependencies with bower
system(sprintf('%s install', $bower), $code);

if (0 !== $code) {
    echo sprintf('Failed to install %s dependencies', $bower);
    exit(1);
}

// Test if composer exists else install it
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

system('bin/setup assets:compile-less');
system('bin/setup assets:build-javascript');
