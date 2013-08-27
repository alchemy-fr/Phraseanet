<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

class ManipulatorServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return array(
            array('Alchemy\Phrasea\Core\Provider\ManipulatorServiceProvider', 'user.manipulator', '\Alchemy\Phrasea\Model\Manipulator\UserManipulator'),
            array('Alchemy\Phrasea\Core\Provider\ManipulatorServiceProvider', 'user.manager', '\Alchemy\Phrasea\Model\Manager\UserManager'),
        );
    }
}
