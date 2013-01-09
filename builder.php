#!/usr/bin/env php
<?php
/*
 * Build Phraseanet for download
 *
 */

printf('Retrieve vendors ...' . PHP_EOL);

system('./vendors.php --no-dev');

require_once __DIR__ . '/lib/classes/bootstrap.class.php';

\bootstrap::register_autoloads();

use Symfony\Component\Finder\Finder;

chdir(__DIR__);

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
    ->name('composer.json')
    ->name('composer.lock')
    ->name('composer.phar')
    ->name('vendors.php')
    ->name('.travis.yml')
    ->name('vendors.win.php')
    ->name('builder.php')
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
