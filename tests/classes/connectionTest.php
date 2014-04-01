<?php

class connectionTest extends \PhraseanetPHPUnitAbstract
{
    public function testMysqlTimeoutIsHandled()
    {
        $conn = connection::getPDOConnection(self::$DI['app']);
        $conn->exec('SET @@local.wait_timeout= 1');
        usleep(1200000);
        $conn->exec('SHOW DATABASES');
        $conn->close();
    }
}
