<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

class ManipulatorServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return array(
            array('Alchemy\Phrasea\Core\Provider\ManipulatorServiceProvider', 'model.user-manipulator', '\Alchemy\Phrasea\Model\Manipulator\UserManipulator'),
            array('Alchemy\Phrasea\Core\Provider\ManipulatorServiceProvider', 'model.user-manager', '\Alchemy\Phrasea\Model\Manager\UserManager'),
        );
    }
}
