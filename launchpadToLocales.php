#!/usr/bin/env php
<?php
/*
 * Upgrade current locale files to the latest launchpad version :
 *
 * You first need to download the latest launchpad version, untar the file, then
 * launch this command with the name of the directory where the po files are as
 * argument.
 *
 * @example ./launchpadToLocales.php phraseanet
 *
 * @see https://translations.launchpad.net/phraseanettranslation/trunk/+pots/phraseanettrunktemplate
 *
 */

require_once __DIR__ . '/lib/Alchemy/Phrasea/Core.php';

use Symfony\Component\Finder\Finder;

\Alchemy\Phrasea\Core::initAutoloads();

if ( ! isset($argv[1]) || ! is_dir(__DIR__ . '/' . $argv[1])) {
    echo "You need to specify a directory with the latest launchpad locales" . PHP_EOL;
    exit(1);
}

chdir(__DIR__);

$finder = new Finder();
$finder
    ->files()
    ->name('phraseanet-*.po')
    ->in(
        array(
            __DIR__ . '/' . $argv[1],
        )
    )
;

$count = 0;

foreach ($finder as $file) {
    preg_match('/phraseanet-(.*)\.po/', $file->getFileName(), $matches);

    $current_file = $file->getRealPath();
    $locale = $matches[1];

    $dest_file = __DIR__ . '/locale/' . $locale . '/LC_MESSAGES/phraseanet.po';

    if ( ! file_exists($dest_file)) {
        echo "Destination $dest_file does not exists" . PHP_EOL;
        continue;
    }

    system(sprintf('cp %s %s', $current_file, $dest_file));

    $count ++;
}

echo "$count files upgraded" . PHP_EOL;

exit($count ? 1 : 0);
