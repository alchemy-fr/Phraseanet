<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

class ModelServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return array(
            array('Alchemy\Phrasea\Core\Provider\ModelServiceProvider', 'model.user-manipulator', '\Alchemy\Phrasea\Model\Manipulator\UserManipulator'),
            array('Alchemy\Phrasea\Core\Provider\ModelServiceProvider', 'model.user-manager', '\Alchemy\Phrasea\Model\Manager\UserManager'),
        );
    }
}
