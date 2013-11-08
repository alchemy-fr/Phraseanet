<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

class ConvertersServiceProvidertest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return array(
            array(
                'Alchemy\Phrasea\Core\Provider\ConvertersServiceProvider',
                'converter.task',
                'Alchemy\Phrasea\Controller\Converter\TaskConverter'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\ConvertersServiceProvider',
                'converter.basket',
                'Alchemy\Phrasea\Controller\Converter\BasketConverter'
            ),
        );
    }
}
