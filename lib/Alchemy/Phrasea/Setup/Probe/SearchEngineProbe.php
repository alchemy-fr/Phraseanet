<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Setup\Probe;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Setup\RequirementCollection;
use Alchemy\Phrasea\SearchEngine\SearchEngineInterface;

class SearchEngineProbe extends RequirementCollection implements ProbeInterface
{
    public function __construct(SearchEngineInterface $searchEngine)
    {
        $this->setName('Search Engine');

        foreach ($searchEngine->getStatus() as $infos) {
            $this->addInformation($infos[0], $infos[1]);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return SearchEngineProbe
     */
    public static function create(Application $app)
    {
        return new static($app['phraseanet.SE']);
    }
}
