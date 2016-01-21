<?php

namespace Alchemy\Phrasea\Core\Database;

use Doctrine\DBAL\Connection;

class SqlDbResetTool
{

    const SOURCE_SUFFIX = '.source';

    public static function dumpDatabase(Connection $connection)
    {
        switch ($connection->getDriver()->getName()) {
            case 'pdo_mysql':
                self::dumpMySql($connection);
                break;
            case 'pdo_sqlite':
                self::dumpSqlite($connection);
                break;
        }
    }

    public static function loadDatabase(Connection $connection)
    {
        switch ($connection->getDriver()->getName()) {
            case 'pdo_mysql':
                self::loadMySql($connection);
                break;
            case 'pdo_sqlite':
                self::loadSqlite($connection);
                break;
        }
    }

    private static function dumpMySql(Connection $connection)
    {
        $targetDbName = $connection->getDatabase() . self::SOURCE_SUFFIX;

        $connection->exec("DROP DATABASE IF EXISTS `$targetDbName`;");
        $connection->exec("CREATE DATABASE `$targetDbName`;");

        $dumpCommand = "mysqldump -u %s -p%s {$connection->getDatabase()} > $targetDbName;";

        shell_exec(sprintf($dumpCommand, $connection->getUsername(), $connection->getPassword()));
    }

    private static function loadMySql(Connection $connection)
    {
        $sourceDbName = $connection->getDatabase() . self::SOURCE_SUFFIX;

        $connection->exec("DROP DATABASE IF EXISTS `{$connection->getDatabase()}`;");
        $connection->exec("CREATE DATABASE `{$connection->getDatabase()}`;");

        $importCommand = "cat $sourceDbName | mysql -u %s -p%s {$connection->getDatabase()}";

        shell_exec(sprintf($importCommand, $connection->getUsername(), $connection->getPassword()));

        $connection->exec("USE {$connection->getDatabase()}");
    }

    private static function dumpSqlite(Connection $connection)
    {
        $targetDbName = $connection->getDatabase() . self::SOURCE_SUFFIX;

        if (file_exists($targetDbName)) {
            unlink($targetDbName);
        }

        copy($connection->getDatabase(), $targetDbName);
    }

    private static function loadSqlite(Connection $connection)
    {
        $sourceDbName = $connection->getDatabase() . self::SOURCE_SUFFIX;
        $targetDbName = $connection->getDatabase();

        if (file_exists($targetDbName)) {
            unlink($targetDbName);
        }

        copy($sourceDbName, $targetDbName);
    }
}
