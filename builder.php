#!/usr/bin/env php
<?php
/*
 * Build Phraseanet for download
 *
 */

printf('Retrieve vendors ...' . PHP_EOL);

system('./vendors.php');

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
  ->name('launchpadToLocales.php')
  ->name('localesToLaunchpad.php')
  ->name('pom.xml')
  ->name('vendors.php')
  ->name('builder.php')
  ->ignoreDotFiles(false)
  ->ignoreVCS(false)
  ->in(__DIR__);

$files = array();

foreach ($finder as $file)
{
  $files[] = $file->getPathname();
}

foreach ($files as $file)
{
  echo "rm $file\n";
  unlink($file);
}

$finder = new Finder();

$finder
  ->directories()
  ->name('test')
  ->name('tests')
  ->name('unitTest')
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
  ->ignoreDotFiles(false)
  ->ignoreVCS(false)
  ->in(__DIR__);


$dirs = array();

foreach ($finder as $dir)
{
  $dirs[] = $dir->getPathname();
}

foreach ($dirs as $dir)
{
  if (!is_dir($dir))
  {
    continue;
  }

  $cmd = sprintf('rm -Rf %s' . PHP_EOL, escapeshellarg($dir));

  printf($cmd);
  system($cmd);
}

exit(0);
