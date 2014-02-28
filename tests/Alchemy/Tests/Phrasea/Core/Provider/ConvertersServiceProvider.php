<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

class ConvertersServiceProvider extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return [
            [
                'Alchemy\Phrasea\Core\Provider\ConvertersServiceProvider',
                'converter.task',
                'Alchemy\Phrasea\Controller\Converter\TaskConverter'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\ConvertersServiceProvider',
                'converter.basket',
                'Alchemy\Phrasea\Controller\Converter\BasketConverter'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\ConvertersServiceProvider',
                'converter.token',
                'Alchemy\Phrasea\Controller\Converter\TokenConverter'
            ],
        ];
    }
}
