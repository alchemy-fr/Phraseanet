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

class OpcodeCacheRequirement extends RequirementCollection
{
    public function __construct()
    {
        $this->setName('Opcode Cache');

        $this->addRecommendation(
            extension_loaded('apc') || class_exists('xcache') || class_exists('wincache'),
            'A cache opcode extension such as apc, xcache or wincache is recommended',
            'Install and enable an opcode cache extension.'
        );
    }
}
