<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @group functional
 * @group legacy
 * @covers Alchemy\Phrasea\Core\Provider\TemporaryFilesystemServiceProvider
 */
class TemporaryFilesystemServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return [
            ['Alchemy\Phrasea\Core\Provider\TemporaryFilesystemServiceProvider', 'temporary-filesystem', 'Neutron\TemporaryFilesystem\TemporaryFilesystemInterface'],
        ];
    }
}
