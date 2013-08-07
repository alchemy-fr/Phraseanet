<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @covers Alchemy\Phrasea\Core\Provider\TemporaryFilesystemServiceProvider
 */
class TemporaryFilesystemServiceProvidertest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return array(
            array('Alchemy\Phrasea\Core\Provider\TemporaryFilesystemServiceProvider', 'temporary-filesystem', 'Neutron\TemporaryFilesystem\TemporaryFilesystemInterface'),
        );
    }
}
