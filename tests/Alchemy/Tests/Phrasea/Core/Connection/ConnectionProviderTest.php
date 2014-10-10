<?php

namespace Alchemy\Tests\Phrasea\Core\Connection;

use Alchemy\Phrasea\Core\Connection\ConnectionProvider;

class ConnectionProviderTest extends \PhraseanetTestCase
{
    public function testMysqlTimeoutIsHandled()
    {
        $provider = new ConnectionProvider(self::$DI['app']['EM.config'], self::$DI['app']['EM.events-manager'], $this->createLoggerMock());
        $conn = $provider->get(self::$DI['app']['conf']->get(['main', 'database']));
        $conn->exec('SET @@local.wait_timeout= 1');
        usleep(1200000);
        $conn->exec('SHOW DATABASES');
    }

    public function testMysqlStatementTimeoutIsHandled()
    {
        $provider = new ConnectionProvider(self::$DI['app']['EM.config'], self::$DI['app']['EM.events-manager'], $this->createLoggerMock());
        $conn = $provider->get(self::$DI['app']['conf']->get(['main', 'database']));
        $conn->exec('SET @@local.wait_timeout= 1');
        $sql = "SELECT POW(:var, 2) AS pow";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue("var", 2);
        usleep(1200000);
        $stmt->execute();
    }
}
