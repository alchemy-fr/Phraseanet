<?php

namespace Alchemy\Phrasea\WorkerManager\Configuration;

class Config
{
    const WORKER_DATABASE_FILE = 'worker.db';

    public static function getPluginDatabaseFile()
    {
        $dbDir = realpath(dirname(__FILE__) . "/../") . "/Db" ;

        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }

        $dbFile = $dbDir . '/' . self::WORKER_DATABASE_FILE;

        if (!is_file($dbFile)) {
            file_put_contents($dbFile, '');
        }

        return $dbFile;
    }

    public static function getWorkerSqliteConnection()
    {
        $db_conn = 'sqlite:'. self::getPluginDatabaseFile();
        $pdo = new \PDO($db_conn);

        return $pdo;
    }
}
