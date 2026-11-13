<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

use Alchemy\Phrasea\Core\Provider\FtpServiceProvider;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class FTPServiceProviderTest extends \PhraseanetTestCase
{
    public function testGetInstantiate()
    {
        self::$DI['app']->register(new FtpServiceProvider());

        $this->assertInstanceOf('Closure', self::$DI['app']['phraseanet.ftp.client']);
    }
}
