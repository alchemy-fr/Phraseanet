<?php

namespace Alchemy\Phrasea\SearchEngine;

use Alchemy\Phrasea\Application;
use Symfony\Component\HttpFoundation\Request;

interface ConfigurationPanelInterface
{
    public function get(Application $app, Request $request);

    public function post(Application $app, Request $request);
}
