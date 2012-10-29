<?php

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\SearchEngine\Phrasea\PhraseaEngine;
use Alchemy\Phrasea\SearchEngine\SphinxSearch\SphinxSearchEngine;

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class SearchEngineTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{

    /**
     * @dataProvider getSearchEngines
     */
    public function testGetConfiguration($searchEngine)
    {
        self::$DI['app']['phraseanet.SE'] = $searchEngine;

        self::$DI['client']->request('GET', '/admin/search-engine/');
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }
    
    /**
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
