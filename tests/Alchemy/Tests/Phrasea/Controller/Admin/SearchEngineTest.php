<?php

namespace Alchemy\Tests\Phrasea\Controller\Admin;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\SearchEngine\Phrasea\PhraseaEngine;
use Alchemy\Phrasea\SearchEngine\SphinxSearch\SphinxSearchEngine;

class SearchEngineTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
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
        $app = new Application('test');

        return array(
            array(new PhraseaEngine($app)),
            array(new SphinxSearchEngine($app, 'localhost', 9306, 'localhost', 9308)),
        );
    }

}
