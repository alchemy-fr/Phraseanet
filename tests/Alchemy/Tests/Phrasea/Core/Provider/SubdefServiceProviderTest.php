<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

use Alchemy\Phrasea\Core\Provider\TasksServiceProvider;
use Alchemy\Tests\Tools\TranslatorMockTrait;

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
        ];
    }
}
