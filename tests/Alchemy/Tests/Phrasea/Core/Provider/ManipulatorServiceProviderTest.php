<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

class ManipulatorServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return array(
            array('Alchemy\Phrasea\Core\Provider\ManipulatorServiceProvider', 'manipulator.user', '\Alchemy\Phrasea\Model\Manipulator\UserManipulator'),
            array('Alchemy\Phrasea\Core\Provider\ManipulatorServiceProvider', 'model.user-manager', '\Alchemy\Phrasea\Model\Manager\UserManager'),
            array(
                'Alchemy\Phrasea\Core\Provider\ManipulatorServiceProvider',
                'manipulator.task',
                'Alchemy\Phrasea\Model\Manipulator\TaskManipulator'
            ),
        );
    }
}
