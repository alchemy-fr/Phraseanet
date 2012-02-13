#!/usr/bin/env php
<?php
/*
 * Look for unused icons in skin directory
 *
 */

require_once __DIR__ . '/lib/classes/bootstrap.class.php';

\bootstrap::register_autoloads();

use Symfony\Component\Finder\Finder;

chdir(__DIR__);

set_time_limit(0);

$finder = new Finder();
$finder
  ->files()
  ->name('*.gif')
  ->name('*.jpeg')
  ->name('*.jpg')
  ->name('*.png')
  ->notName('ui-*.png')
  ->exclude(array('substitution', 'client/959595/images', 'client/000000/images', 'client/FFFFFF/images'))
  ->in(__DIR__ . '/www/skins');

$files = array();

foreach ($finder as $file)
{
  $cmd = "grep -IR -m 1 --exclude='*\.git*' '".str_replace(array(), array(), $file->getFilename())."' ".__DIR__;

  $result = system($cmd);

  if(trim($result) === '')
  {
    $files[] = $file->getPathname();
  }
  echo ". ";
}

foreach($files as $file)
{
  unlink($file);
}

exit(0);
