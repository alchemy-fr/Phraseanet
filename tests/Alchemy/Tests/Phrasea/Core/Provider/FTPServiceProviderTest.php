<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

use Alchemy\Phrasea\Core\Provider\FtpServiceProvider;

class FTPServiceProvidertest extends \PhraseanetPHPUnitAbstract
{
    public function testGetInstantiate()
    {
        self::$DI['app']->register(new FtpServiceProvider());

        $ftpclient1 = self::$DI['app']['phraseanet.ftp.client'];
        $ftpclient2 = self::$DI['app']['phraseanet.ftp.client'];
        $this->assertInstanceof('ftpclient', $ftpclient1);
        $this->assertInstanceof('ftpclient', $ftpclient2);

        $this->assertNotEquals($ftpclient1, $ftpclient2);
    }
}
