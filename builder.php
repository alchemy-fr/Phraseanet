#!/usr/bin/env php
<?php
/*
 * Build Phraseanet for download
 *
 */

use Symfony\Component\Finder\Finder;

printf('Retrieve vendors ...' . PHP_EOL);

system('./bin/developer dependencies:all --no-dev', $exitcode);

if (0 !== $exitcode) {
    echo "failed to retrieve binaries\n";
    exit(1);
}

require __DIR__ . '/vendor/autoload.php';

set_time_limit(0);

printf('Remove files ...' . PHP_EOL);

$finder = new Finder();
$finder
    ->files()
    ->name('.gitmodules')
    ->name('.gitignore')
    ->name('check_cs.php')
    ->name('cleaner.php')
    ->name('build-env.sh')
    ->name('phpunit.xml.dist')
    ->name('launchpadToLocales.php')
    ->name('localesToLaunchPad.php')
    ->name('pom.xml')
    ->name('bower.json')
    ->name('composer.json')
    ->name('composer.lock')
    ->name('composer.phar')
    ->name('vendors.php')
    ->name('.travis.yml')
    ->name('vendors.win.php')
    ->name('builder.php')
    ->name('behat.yml')
    ->name('behat.yml.sample')
    ->ignoreDotFiles(false)
    ->ignoreVCS(false)
    ->in(__DIR__);

$files = [];

foreach ($finder as $file) {
    $files[$file->getRealpath()] = $file->getRealpath();
}

foreach ([
    __DIR__ . '/bin/behat',
    __DIR__ . '/bin/developer',
    __DIR__ . '/bin/doctrine.php',
    __DIR__ . '/bin/doctrine',
    __DIR__ . '/bin/phpunit',
    __DIR__ . '/bin/validate-json',
] as $binary) {
    if (is_file($binary)) {
        $files[$binary] = $binary;
    }
}

$finder = new Finder();
$finder
    ->ignoreDotFiles(false)
    ->ignoreVCS(false)
    ->in(['logs']);

foreach ($finder as $file) {
    $files[$file->getRealpath()] = $file->getRealpath();
}

foreach ($files as $file) {
    echo "rm $file\n";
    unlink($file);
}

$finder = new Finder();

$finder
    ->directories()
    ->name('test')
    ->name('tests')
    ->name('functionnal-tests')
    ->name('Tests')
    ->name('test-suite')
    ->name('test_script')
    ->name('demos')
    ->name('demo')
    ->name('example')
    ->name('examples')
    ->name('docs')
    ->name('documentation')
    ->name('doc')
    ->name('as-docs')
    ->name('hudson')
    ->name('.svn')
    ->name('.git')
    ->name('flash')
    ->name('qunit')
    ->name('features')
    ->name('chai')
    ->name('mocha')
    ->name('sinon')
    ->name('sinon-chai')
    ->name('js-fixtures')
    ->name('node_modules')
    ->name('tmp-assets')
    ->ignoreDotFiles(false)
    ->ignoreVCS(false)
    ->in(__DIR__);


$dirs = [];

foreach ($finder as $dir) {
    $dirs[] = $dir->getRealpath();
}

foreach ($dirs as $dir) {
    if ( ! is_dir($dir)) {
        continue;
    }

    $cmd = sprintf('rm -Rf %s' . PHP_EOL, escapeshellarg($dir));

    printf($cmd);
    system($cmd);
}

exit(0);
