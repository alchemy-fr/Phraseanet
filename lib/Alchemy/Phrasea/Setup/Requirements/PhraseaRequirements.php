<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Setup\Requirements;

use Alchemy\Phrasea\Setup\System\RequirementCollection;
use Symfony\Component\Process\ExecutableFinder;

class PhraseaRequirements extends RequirementCollection
{
    const PHRASEA_EXTENSION_VERSION = '1.21.1.0';
    const PHRASEA_INDEXER_VERSION = '3.10.2.3';

    public function __construct($binaries = array())
    {
        $this->setName('Phrasea');

        $this->addRecommendation(
            function_exists('phrasea_fetch_results'),
            'phrasea extension is required to use Phrasea search-engine',
            'Install and enable the <strong>phrasea</strong> extension to enable Phrasea search-engine (https://github.com/alchemy-fr/Phraseanet-Extension).'
        );

        if (function_exists('phrasea_info')) {
            $infos = phrasea_info();

            $this->addRequirement(
                version_compare($infos['version'], static::PHRASEA_EXTENSION_VERSION, '>='),
                sprintf('Phrasea extension version %s is required (version %s installed)', static::PHRASEA_EXTENSION_VERSION, $infos['version']),
                'Update <strong>phrasea</strong> extension to the latest stable (https://github.com/alchemy-fr/Phraseanet-Extension).'
            );
            $this->addRequirement(
                true === $infos['temp_writable'],
                'phrasea extension should be able to write in its temporary directory (current is ' . $infos['temp_dir'] . ')',
                'Change directory <strong>' . $infos['temp_dir'] . '</strong> mode so phrasea extension could write to it'
            );
        }

        $executableFinder = new ExecutableFinder();
        $indexer = isset($binaries['phraseanet_indexer']) ? $binaries['phraseanet_indexer'] : $executableFinder->find('phraseanet_indexer');

        if (null !== $indexer) {
            $output = null;
            exec($indexer . ' --version', $output);
            $data = sscanf($output[0], 'phraseanet_indexer version %d.%d.%d.%d');
            $version = sprintf('%d.%d.%d.%d', $data[0], $data[1], $data[2], $data[3]);

            $this->addRequirement(
                version_compare(static::PHRASEA_INDEXER_VERSION, $version, '<'),
                sprintf('Phraseanet Indexer %s or higher is required (%s provided)', static::PHRASEA_INDEXER_VERSION, $version),
                'Please update to a more recent version'
            );
        } elseif (function_exists('phrasea_info')) {
            $this->addRecommendation(
                false,
                sprintf('Phraseanet Indexer %s or higher is required', static::PHRASEA_INDEXER_VERSION),
                'Please update to a more recent version'
            );
        }
    }
}
