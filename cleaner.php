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
  ->exclude(
    array(
      'substitution'
      , 'client/959595/images'
      , 'client/000000/images'
      , 'client/FFFFFF/images'
      , 'skins/lng'
  )
  )
  ->in(__DIR__ . '/www/skins');

$files = array();

foreach ($finder as $file)
{
  $result = '';

  foreach (array('templates', 'lib/Alchemy', 'lib/Doctrine', 'lib/classes', 'www') as $dir)
  {
    $cmd = "grep -IR -m 1 --exclude='(*\.git*)' '" . str_replace(array(), array(), $file->getFilename()) . "' " . __DIR__.'/'.$dir;
    $result .= @exec($cmd);

    if (trim($result) !== '')
    {
      break;
    }
  }

  if (trim($result) === '')
  {
    $files[] = $file->getPathname();
  }
}

foreach ($files as $file)
{
  echo "rm $file\n";
  unlink($file);
}

exit(0);
