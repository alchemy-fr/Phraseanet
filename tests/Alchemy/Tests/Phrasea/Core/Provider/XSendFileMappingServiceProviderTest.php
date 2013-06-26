<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @covers Alchemy\Phrasea\Core\Provider\FileServeServiceProvider
 */
class XSendFileMappingServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return array(
            array('Alchemy\Phrasea\Core\Provider\XSendFileMappingServiceProvider', 'phraseanet.xsendfile-mapping', 'Alchemy\\Phrasea\\XSendFile\\Mapping'),
        );
    }
}
