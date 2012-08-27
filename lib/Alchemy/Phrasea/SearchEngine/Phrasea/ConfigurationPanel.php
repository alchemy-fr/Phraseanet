<?php

namespace Alchemy\Phrasea\SearchEngine\Phrasea;

use Silex\Application;

class ConfigurationPanel
{
    protected $charsets;
    protected $searchEngine;

    public function __construct(SphinxSearchEngine $engine)
    {
        $this->searchEngine = $engine;
    }

    public function get(Application $app, Request $request)
    {

        return $app['twig']->render('admin/search-engine/phrasea.html.twig', array());
    }

    public function post(Application $app, Request $request)
    {

    }

}
