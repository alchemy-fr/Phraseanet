<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @covers Alchemy\Phrasea\Core\Provider\PluginServiceProvider
 */
#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class PluginServiceProviderTest extends ServiceProviderTestCase
{
    public static function provideServiceDescription()
    {
        return [
            [
                'Alchemy\Phrasea\Core\Provider\PluginServiceProvider',
                'plugins.json-validator',
                'JsonSchema\Validator'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\PluginServiceProvider',
                'plugins.manager',
                'Alchemy\Phrasea\Plugin\PluginManager'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\PluginServiceProvider',
                'plugins.plugins-validator',
                'Alchemy\Phrasea\Plugin\Schema\PluginValidator'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\PluginServiceProvider',
                'plugins.manifest-validator',
                'Alchemy\Phrasea\Plugin\Schema\ManifestValidator'
            ],
        ];
    }
}
