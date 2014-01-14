<?php

namespace Alchemy\Tests\Phrasea\Core\CLIProvider;

/**
 * @covers Alchemy\Phrasea\Core\CLIProvider\LessBuilderServiceProvider
 */
class LessBuilderServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return [
            [
                'Alchemy\Phrasea\Core\CLIProvider\LessBuilderServiceProvider',
                'phraseanet.less-compiler',
                '\Alchemy\Phrasea\Utilities\Less\Compiler'
            ],
            [
                'Alchemy\Phrasea\Core\CLIProvider\LessBuilderServiceProvider',
                'phraseanet.less-builder',
                '\Alchemy\Phrasea\Utilities\Less\Builder'
            ],
        ];
    }
}
