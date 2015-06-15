<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @group functional
 * @group legacy
 */
class SubdefServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return [
            [
                'Alchemy\Phrasea\Core\Provider\SubdefServiceProvider',
                'subdef.generator',
                'Alchemy\Phrasea\Media\SubdefGenerator'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\SubdefServiceProvider',
                'subdef.substituer',
                'Alchemy\Phrasea\Media\SubdefSubstituer'
            ],
        ];
    }
}
