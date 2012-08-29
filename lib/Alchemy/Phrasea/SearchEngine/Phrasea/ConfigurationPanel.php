<?php

namespace Alchemy\Phrasea\SearchEngine\Phrasea;

use Alchemy\Phrasea\SearchEngine\ConfigurationPanelInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class ConfigurationPanel implements ConfigurationPanelInterface
{
    protected $charsets;
    protected $searchEngine;

    public function __construct(PhraseaEngine $engine)
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
