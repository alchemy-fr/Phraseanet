<?php

namespace Alchemy\Phrasea\Core\Database;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

class ConnectionChecker
{
    /**
     * Tells whether the database server still answers on the given connection.
     */
    public static function isAlive(Connection $connection)
    {
        try {
            $connection->executeQuery($connection->getDatabasePlatform()->getDummySelectSQL());

            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
