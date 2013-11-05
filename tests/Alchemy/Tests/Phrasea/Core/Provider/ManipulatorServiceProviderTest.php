<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

class ManipulatorServiceProvidertest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return array(
            array(
                'Alchemy\Phrasea\Core\Provider\ManipulatorServiceProvider',
                'manipulator.task',
                'Alchemy\Phrasea\Model\Manipulator\TaskManipulator'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\ManipulatorServiceProvider',
                'manipulator.acl',
                'Alchemy\Phrasea\Model\Manipulator\ACLManipulator'
            ),
        );
    }
}
