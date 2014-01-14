<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

class ManipulatorServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return [
            [
                'Alchemy\Phrasea\Core\Provider\ManipulatorServiceProvider',
                'manipulator.task',
                'Alchemy\Phrasea\Model\Manipulator\TaskManipulator'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\ManipulatorServiceProvider',
                'manipulator.acl',
                'Alchemy\Phrasea\Model\Manipulator\ACLManipulator'
            ],
        ];
    }
}
