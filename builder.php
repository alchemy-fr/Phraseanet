#!/usr/bin/env php
<?php
/*
 * Build Phraseanet for download
 *
 */

use Symfony\Component\Finder\Finder;

printf('Retrieve vendors ...' . PHP_EOL);

system('./vendors.php --no-dev');

require __DIR__ . '/vendor/autoload.php';

chdir(__DIR__);

system('bin/setup assets:compile-less', $code);

if (0 !== $code) {
    echo "Failed to build less files\n";
    exit(1);
}

system('bin/setup assets:build-javascript', $code);

if (0 !== $code) {
    echo "Failed to build javascript files\n";
    exit(1);
}

set_time_limit(0);

printf('Remove files ...' . PHP_EOL);

$finder = new Finder();
$finder
    ->files()
    ->name('.gitmodules')
    ->name('.gitignore')
    ->name('check_cs.php')
    ->name('bin/behat')
    ->name('bin/developer')
    ->name('bin/doctrine.php')
    ->name('bin/doctrine')
    ->name('bin/phpunit')
    ->name('cleaner.php')
    ->name('build-env.sh')
    ->name('phpunit.xml.dist')
    ->name('launchpadToLocales.php')
    ->name('localesToLaunchPad.php')
    ->name('pom.xml')
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

$files = array();

foreach ($finder as $file) {
    $files[] = $file->getPathname();
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
    ->name('angular-mocks')
    ->name('angular-scenario')
    ->name('qunit')
    ->name('features')
    ->name('chai')
    ->name('mocha')
    ->name('sinon')
    ->name('sinon-chai')
    ->name('js-fixtures')
    ->ignoreDotFiles(false)
    ->ignoreVCS(false)
    ->in(__DIR__);


$dirs = array();

foreach ($finder as $dir) {
    $dirs[] = $dir->getPathname();
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
