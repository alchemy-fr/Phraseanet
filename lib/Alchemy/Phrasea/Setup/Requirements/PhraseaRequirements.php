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

class PhraseaRequirements extends RequirementCollection
{
    public function __construct()
    {
        $this->setName('Phrasea');

        $this->addRecommendation(
            function_exists('phrasea_fetch_results'),
            'phrasea extension is required to use Phrasea search-engine',
            'Install and enable the <strong>phrasea</strong> extension to enable Phrasea search-engine (https://github.com/alchemy-fr/Phraseanet-Extension).'
        );

        if (function_exists('phrasea_fetch_results')) {
            $infos = phrasea_info();

            $this->addRequirement(
                version_compare($infos['version'], '1.21.0.1', '>='),
                'phrasea extension version 1.21.0.1 is required (version ' . $infos['version'] . ' installed)',
                'Update <strong>phrasea</strong> extension to the latest stable (https://github.com/alchemy-fr/Phraseanet-Extension).'
            );
            $this->addRequirement(
                true === $infos['temp_writable'],
                'phrasea extension should be able to write in its temporary directory (current is ' . $infos['temp_dir'] . ')',
                'Change directory <strong>' . $infos['temp_dir'] . '</strong> mode so phrasea extension could write to it'
            );
        }
    }
}
