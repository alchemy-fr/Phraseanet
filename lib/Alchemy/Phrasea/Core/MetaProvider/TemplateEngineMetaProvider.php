<?php

namespace Alchemy\Phrasea\Core\MetaProvider;

use Alchemy\Phrasea\Core\Provider\TwigServiceProvider as PhraseanetTwigServiceProvider;
use Silex\Application;
use Silex\Provider\TwigServiceProvider;
use Silex\ServiceProviderInterface;

class TemplateEngineMetaProvider implements ServiceProviderInterface
{

    public function register(Application $app)
    {
        $app->register(new TwigServiceProvider());
        $app->register(new PhraseanetTwigServiceProvider());
    }

    public function boot(Application $app)
    {
        // no-op
    }
}
