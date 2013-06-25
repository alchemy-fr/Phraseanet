<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @covers Alchemy\Phrasea\Core\Provider\FileServeServiceProvider
 */
class FileServeServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return array(
            array('Alchemy\Phrasea\Core\Provider\FileServeServiceProvider', 'phraseanet.file-serve', 'Alchemy\\Phrasea\\Response\\ServeFileResponseFactory'),
        );
    }
}
