<?php

namespace Alchemy\Phrasea\SearchEngine;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

interface ConfigurationPanelInterface
{

    public function get(Application $app, Request $request);

    public function post(Application $app, Request $request);
}
