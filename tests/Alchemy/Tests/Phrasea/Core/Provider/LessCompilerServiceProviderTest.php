<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @covers Alchemy\Phrasea\Core\Provider\UnicodeServiceProvider
 */
class LessBuilderServiceProvidertest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return array(
            array('Alchemy\Phrasea\Core\Provider\LessBuilderServiceProvider', 'phraseanet.less-builder', '\Alchemy\Phrasea\Utilities\Less\Builder'),
        );
    }
}
