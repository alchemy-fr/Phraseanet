<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Setup\Requirements;

use Alchemy\Phrasea\Setup\RequirementCollection;

class CacheServerRequirement extends RequirementCollection implements RequirementInterface
{
    public function __construct()
    {
        $this->setName('Cache Server');

        $this->addRecommendation(
            class_exists('Memcached') || class_exists('Memcache') || class_exists('Redis'),
            'A cache server extension such as Memcached, Memcache or Redis is recommended',
            'Install and enable a cache server extension.'
        );
    }
}
