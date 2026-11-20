<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class RandomGeneratorServiceProviderTest extends ServiceProviderTestCase
{
    public static function provideServiceDescription()
    {
        return [
            [
                'Alchemy\Phrasea\Core\Provider\RandomGeneratorServiceProvider',
                'random.factory',
                'Randomlib\Factory'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\RandomGeneratorServiceProvider',
                'random.low',
                'Randomlib\Generator'
            ],
        ];
    }
}
