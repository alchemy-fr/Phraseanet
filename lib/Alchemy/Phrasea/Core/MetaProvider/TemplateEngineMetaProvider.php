<?php

namespace Alchemy\Phrasea\Core\MetaProvider;

use Alchemy\Phrasea\Core\Provider\TwigServiceProvider as PhraseanetTwigServiceProvider;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Provider\TwigServiceProvider;

class TemplateEngineMetaProvider implements ServiceProviderInterface
{

    public function register(Container $app)
    {
        $app->register(new TwigServiceProvider());
        $app->register(new PhraseanetTwigServiceProvider());
    }
}
