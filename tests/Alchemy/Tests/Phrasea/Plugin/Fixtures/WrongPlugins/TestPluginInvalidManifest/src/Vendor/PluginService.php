<?php

namespace Vendor;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Silex\Application;
use Alchemy\Phrasea\Plugin\PluginProviderInterface;

class PluginService implements PluginProviderInterface
{
    public function register(Application $app)
    {
        $app['plugin-test'] = 'hello world';
    }

    public function boot(Application $app)
    {
    }

    public static function create(PhraseaApplication $app)
    {
        return new static();
    }
}
