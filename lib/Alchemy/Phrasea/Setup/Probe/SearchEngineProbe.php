<?php

namespace Alchemy\Phrasea\Setup\Probe;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Setup\System\RequirementCollection;
use Alchemy\Phrasea\SearchEngine\SearchEngineInterface;

class SearchEngineProbe extends RequirementCollection implements ProbeInterface
{
    public function __construct(SearchEngineInterface $searchEngine)
    {
        $this->setName('Search Engine');
        
        foreach ($searchEngine->getStatus() as $infos)
        {
            $this->addInformation($infos[0], $infos[1]);
        }
    }

    public static function create(Application $app)
    {
        return new static($app['phraseanet.SE']);
    }
}
