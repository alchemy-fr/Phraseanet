<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

class RandomGeneratorServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
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
