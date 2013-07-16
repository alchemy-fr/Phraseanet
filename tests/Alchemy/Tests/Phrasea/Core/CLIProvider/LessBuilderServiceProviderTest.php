<?php

namespace Alchemy\Tests\Phrasea\Core\CLIProvider;

/**
 * @covers Alchemy\Phrasea\Core\CLIProvider\LessBuilderServiceProvider
 */
class LessBuilderServiceProvidertest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return array(
            array(
                'Alchemy\Phrasea\Core\Provider\LessBuilderServiceProvider',
                'phraseanet.less-compiler',
                '\Alchemy\Phrasea\Utilities\Less\Compiler'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\LessBuilderServiceProvider',
                'phraseanet.less-builder',
                '\Alchemy\Phrasea\Utilities\Less\Builder'
            ),
        );
    }
}
