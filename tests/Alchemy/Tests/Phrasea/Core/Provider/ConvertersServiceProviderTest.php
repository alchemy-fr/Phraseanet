<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

class ConvertersServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return [
            [
                'Alchemy\Phrasea\Core\Provider\ConvertersServiceProvider',
                'converter.task',
                'Alchemy\Phrasea\Model\Converter\TaskConverter'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\ConvertersServiceProvider',
                'converter.basket',
                'Alchemy\Phrasea\Model\Converter\BasketConverter'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\ConvertersServiceProvider',
                'converter.token',
                'Alchemy\Phrasea\Model\Converter\TokenConverter'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\ConvertersServiceProvider',
                'converter.api-application',
                'Alchemy\Phrasea\Model\Converter\ApiApplicationConverter'
            ],
        ];
    }
}
