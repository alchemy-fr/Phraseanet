<?php

namespace Alchemy\Tests\Phrasea\Core\Connection;

use Alchemy\Phrasea\Core\Connection\ConnectionProvider;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class ConnectionPoolManager extends \PhraseanetTestCase
{
    public function __construct()
    {
        parent::__construct(static::class);
    }
    public function testMysqlTimeoutIsHandled()
    {
        $this->markTestSkipped();
        $provider = new ConnectionProvider(self::$DI['app']['db.config'], self::$DI['app']['db.event_manager'], $this->createLoggerMock());
        $conn = $provider->get(self::$DI['app']['conf']->get(['main', 'database']));
        $conn->exec('SET @@local.wait_timeout= 1');
        usleep(1200000);
        $conn->exec('SHOW DATABASES');
    }
}
