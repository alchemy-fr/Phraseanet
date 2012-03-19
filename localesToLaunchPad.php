#!/usr/bin/env php
<?php
/*
 * Prepare the file to be uploaded on launchpad 
 * 
 * Fetch every translation at the root of ./locale, next to the main pot file
 * 
 * @see https://translations.launchpad.net/phraseanettranslation/trunk/+pots/phraseanettrunktemplate
 *
 */

require_once __DIR__ . '/lib/Alchemy/Phrasea/Core.php';

use Symfony\Component\Finder\Finder;

\Alchemy\Phrasea\Core::initAutoloads();

chdir(__DIR__);

$finder = new Finder();
$finder
  ->files()
  ->name('phraseanet.po')
  ->in(
    array(
      __DIR__ . '/locale',
    )
  )
;

$count = 0;

foreach ($finder as $file)
{
  $cmd = sprintf(
      'cp %s ./locale/phraseanet-%s.po'
      , $file->getRealPath()
      , basename(dirname(dirname($file->getRealPath())))
    ) . PHP_EOL;

  system($cmd);
  $count++;
}

echo "$count files copied" . PHP_EOL;

exit($count ? 1 : 0);
