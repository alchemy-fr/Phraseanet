<?php

namespace Vendor;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Plugin\PluginProviderInterface;
use Pimple\Container;

class PluginService implements PluginProviderInterface
{
    public function register(Container $app)
    {
        $app['plugin-test'] = 'hello world';
    }

    public static function create(PhraseaApplication $app)
    {
        return new static();
    }
}
