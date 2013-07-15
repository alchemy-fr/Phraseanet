<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @covers Alchemy\Phrasea\Core\Provider\UnicodeServiceProvider
 */
class LessCompilerServiceProvidertest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return array(
            array('Alchemy\Phrasea\Core\Provider\LessCompilerServiceProvider', 'phraseanet.less-compiler', '\Alchemy\Phrasea\Utilities\Less\Compiler'),
        );
    }
}
