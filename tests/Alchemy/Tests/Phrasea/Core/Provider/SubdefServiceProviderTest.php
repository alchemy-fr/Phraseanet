<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class SubdefServiceProviderTest extends ServiceProviderTestCase
{
    public static function provideServiceDescription()
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
