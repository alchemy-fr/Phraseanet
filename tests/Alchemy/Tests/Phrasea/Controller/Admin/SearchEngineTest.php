<?php

namespace Alchemy\Tests\Phrasea\Controller\Admin;

use Alchemy\Phrasea\SearchEngine\Elastic\ElasticSearchEngine;
use Alchemy\Phrasea\SearchEngine\Phrasea\PhraseaEngine;
use Alchemy\Phrasea\SearchEngine\SphinxSearch\SphinxSearchEngine;

class SearchEngineTest extends \PhraseanetAuthenticatedWebTestCase
{

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\SearchEngine::getSearchEngineConfigurationPanel
     * @dataProvider getSearchEngines
     */
    public function testGetConfiguration($searchEngine)
    {
        self::$DI['app']['phraseanet.SE'] = $searchEngine;

        self::$DI['client']->request('GET', '/admin/search-engine/');
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\SearchEngine::postSearchEngineConfigurationPanel
     * @dataProvider getSearchEngines
     */
    public function testPostConfiguration($searchEngine)
    {
        self::$DI['app']['phraseanet.SE'] = $searchEngine;

        self::$DI['client']->request('POST', '/admin/search-engine/');
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
    }

    public function getSearchEngines()
    {
        $app = $this->loadApp();

        $SE = [[new SphinxSearchEngine($app, 'localhost', 9306, 'localhost', 9308)]];

        if (extension_loaded('phrasea2')) {
            $SE[] = [new PhraseaEngine($app)];
        }
        if (false !== $ret = @file_get_contents('http://localhost:9200')) {
            $SE[] = [ElasticSearchEngine::create($app)];
        }

        return $SE;
    }

}
