#!/usr/bin/env php
<?php
/*
 * Build Phraseanet for download
 * 
 */

require_once __DIR__ . '/lib/bootstrap.php';

use Symfony\Component\Finder\Finder;

$fix = isset($argv[1]) && 'fix' == $argv[1];

chdir(__DIR__);

set_time_limit(0);

printf('Retrieve vendors ...' . PHP_EOL);

system('./vendors.php');

$finder = new Finder();
$finder
  ->directories()
  ->name('test')
  ->name('tests')
  ->name('unitTests')
  ->name('demos')
  ->name('demo')
  ->name('example')
  ->name('examples')
  ->name('.svn')
  ->name('.git')
  ->in(
    array(
      __DIR__ . '/lib',
      __DIR__ . '/bin',
      __DIR__ . '/config',
      __DIR__ . '/www',
      __DIR__ . '/templates'
    )
  )
  ->exclude('vendor')
;

foreach ($finder as $dir)
{
  $cmd = sprintf('rm -Rf %s' . PHP_EOL, escapeshellarg($dir->getPathname()));

  if ($fix)
    system($cmd);
  else
    printf($cmd);
}

$root_files = array('hudson', 'check_cs.php', 'pom.xml', 'vendors.php', 'builder.php');


foreach ($root_files as $file)
{
  $cmd = sprintf('rm -Rf %s/%s' . PHP_EOL, __DIR__, escapeshellarg($file));

  if ($fix)
    system($cmd);
  else
    printf($cmd);
}

exit(0);
