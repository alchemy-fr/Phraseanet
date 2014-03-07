<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Setup\Version\PreSchemaUpgrade;

use Alchemy\Phrasea\Application;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Schema\Column;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;

class Upgrade39Fusion implements PreSchemaUpgradeInterface
{
    private $app;
    private static $tables = [
        "clients",
        "coll",
        "collusr",
        "exports",
        "histo",
        "metadatas_structure",
        "metadatas",
        "status",
        "log",
        "log_colls",
        "log_search",
        "log_docs",
        "log_view",
        "log_thumb",
        "permalinks",
        "pref",
        "quest",
        "record",
        "regroup",
        "subdef",
        "technical_datas",
    ];
    private static $refsConf = [
            'coll' => [
                'coll_id' => [
                    'collusr' => 'coll_id',
                    'record' => 'coll_id',
                    'log_colls' => 'coll_id',
                    'log_search' => 'coll_id',
                    'quest' => 'coll_id'
                ]
            ],
            'exports' => [
                'id' => []
            ],
            'quest' => [
                'id' => []
            ],
            'histo' => [
                'id' => []
            ],
            'log' => [
                'id' => [
                    'log_view' => 'log_id',
                    'log_search' => 'log_id',
                    'log_docs' => 'log_id',
                    'log_colls' => 'log_id',
                    'export' => 'logid',
                    'log_colls' => 'logid',
                    'quest' => 'logid',
                ]
            ],
            'log_colls' => [
                'id' => []
            ],
            'log_docs' => [
                'id' => []
            ],
            'log_search' => [
                'id' => []
            ],
            'log_view' => [
                'id' => []
            ],
            'metadatas_structure' => [
                'id' => [
                    'metadatas' => 'meta_struct_id',
                ]
            ],
            'metadatas' => [
                'id' => []
            ],
            'record' => [
                'record_id' => [
                    'subdef' => 'record_id',
                    'regroup' => ['rid_parent', 'rid_child'],
                    'technical_datas' => 'record_id',
                    'status'  => 'record_id',
                    'metadatas' => 'record_id',
                    'log_view' => 'record_id',
                    'log_thumb' => 'record_id',
                    'log_docs' => 'record_id',
                    'exports' => 'rid',
                    'histo' => 'record',
                    'record' => 'parent_record_id',
                ]
            ],
            'permalinks' => [
                'id' => []
            ],
            'pref' => [
                'id' => []
            ],
            'regroup' => [
                'id' => []
            ],
            'status' => [
                'id' => []
            ],
            'subdef' => [
                'subdef_id' => [
                    'permalinks' => 'subdef_id',
                ]
            ],
            'technical_datas' => [
                'id' => []
            ],
        ];
    private static $tableWithSbasId = ['coll', 'pref', 'metadatas_structure'];
    
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(EntityManager $em, \appbox $appbox, Configuration $conf)
    {
        $first = true;

        foreach ($this->getSbasIds($em) as $row) {
            $connsbas = $this->getDataboxConnection($row['dbname']);

            $this->addSbasIdsOnTables($connsbas, $row['sbas_id']);
            $this->upgradeRefs($em->getConnection(), $connsbas);

            if ($first) {
                $this->createTables($em->getConnection(), $connsbas, $row['dbname']);
                $first = false;
            }

            $this->insertTables($em->getConnection(), $row['dbname']);
        }

        $this->upgradeServerCollIds($em->getConnection());
        $this->upgradeRecordIds($em->getConnection());

        $this->updateConnections($em);
    }

    private function upgradeServerCollIds(Connection $conn)
    {
        $sql = "UPDATE bas b
                    INNER JOIN coll c ON (
                        b.sbas_id = c.sbas_id
                        AND b.server_coll_id = c.coll_id_BC
                    )
                SET b.server_coll_id = c.coll_id";
        $conn->executeUpdate($sql);
    }

    private function upgradeRecordIds(Connection $conn)
    {
        $tablesWithJoinOnSbas = [
            'BasketElements' => 'record_id',
            'bridge_elements' => 'record_id',
            'FeedItems' => 'record_id',
            'recusr' => 'record_id',
            'ssel' => 'rid',
            'StoryWZ' => 'record_id',
        ];
        $tablesWithJoinOnBas = [
            'FtpExportElements' => 'record_id',
            'ftp_export_elements' => 'record_id',
            'OrderElements' => 'record_id',
            'order_elements' => 'record_id',
            'sselcont' => 'record_id',
        ];

        foreach ($tablesWithJoinOnSbas as $table => $column) {
            if ($this->tableExists($conn, $table)) {
                $sql = "UPDATE ".$table." t
                    INNER JOIN (record r, coll c)
                    ON (
                        r.record_id_BC = t.".$column."
                        AND r.coll_id = c.coll_id
                        AND t.sbas_id = c.sbas_id
                    ) SET t.".$column." = r.record_id";
                $conn->exec($sql);
            }
        }

        foreach ($tablesWithJoinOnBas as $table => $column) {
            if ($this->tableExists($conn, $table)) {
                $sql = "UPDATE ".$table." t
                    INNER JOIN (record r, bas b)
                    ON (
                        r.record_id_BC = t.".$column."
                        AND r.coll_id = b.server_coll_id
                        AND t.base_id = b.base_id
                    ) SET t.".$column." = r.record_id";
                $conn->exec($sql);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isApplyable(Application $app)
    {
        return !$this->tableExists($app['EM']->getConnection(), 'record');
    }

    /**
     * {@inheritdoc}
     */
    public function rollback(EntityManager $em, \appbox $appbox, Configuration $conf)
    {
        $manager = $em->getConnection()->getSchemaManager();

        foreach (static::$tables as $table) {
            if ($this->tableExists($em->getConnection(), $table)) {
                $manager->dropTable($table);
            }
        }

        foreach ($this->getSbasIds($em) as $row) {
            $connsbas = $this->getDataboxConnection($row['dbname']);

            $this->downgradeRefs($connsbas);

            foreach (static::$tableWithSbasId as $table) {
                if ($this->tableHasField($connsbas, $table, 'sbas_id')) {
                    $sql = 'ALTER TABLE `'.$table.'` DROP `sbas_id`';
                    $connsbas->exec($sql);
                }
            }
        }
    }

    private function getDataboxConnection($dbname)
    {
        return $this->getConnection(array_replace($this->app['conf']->get(['main', 'database']), ['dbname' => $dbname]));
    }

    private function getConnection(array $params)
    {
        return DriverManager::getConnection($params, $this->app['EM.config'], $this->app['EM.events-manager']);
    }

    private function getSbasIds(EntityManager $em)
    {
        $sql = 'SELECT sbas_id, dbname FROM sbas';

        return $em->getConnection()->fetchAll($sql);
    }

    private function updateConnections(EntityManager $em)
    {
        try {
            $sql = 'ALTER TABLE sbas DROP INDEX server';
            $em->getConnection()->executeUpdate($sql);
        } catch (\Exception $e) {
        }

        $sql = 'UPDATE sbas SET viewname = dbname WHERE viewname IS NULL OR viewname =""';
        $em->getConnection()->executeUpdate($sql);

        $sql = 'UPDATE sbas SET host = :host, port = :port, dbname = :dbname, user = :user, pwd = :password';
        $em->getConnection()->executeUpdate($sql, [
            'host' => $this->app['conf']->get(['main', 'database', 'host']),
            'port' => $this->app['conf']->get(['main', 'database', 'port']),
            'dbname' => $this->app['conf']->get(['main', 'database', 'dbname']),
            'user' => $this->app['conf']->get(['main', 'database', 'user']),
            'password' => $this->app['conf']->get(['main', 'database', 'password']),
        ]);

        foreach ($this->app['phraseanet.appbox']->get_databoxes() as $databox) {
            $databox->set_connection($em->getConnection());
        }
    }

    private function addSbasIdsOnTables(Connection $connsbas, $sbasId)
    {
        foreach (static::$tableWithSbasId as $table) {
            if ($this->tableExists($connsbas, $table)) {
                $sql = "ALTER TABLE `$table` ADD  `sbas_id` INT( 11 ) UNSIGNED NOT NULL DEFAULT  '$sbasId', ADD INDEX (  `sbas_id` )";
                $connsbas->exec($sql);
            }
        }
    }

    private function createTables(Connection $conn, Connection $connsbas, $dbname)
    {
        $platform = $connsbas->getDatabasePlatform();
        $platform->registerDoctrineTypeMapping('enum', 'string');

        $sqls = $connsbas->getSchemaManager()->createSchema()->toSql($platform);

        foreach ($sqls as $sql) {
            $matches = array();
            preg_match('/^CREATE TABLE ([a-zA-Z0-9\-_]+)/', $sql, $matches);
            if (!isset($matches[1]) || !in_array($matches[1], static::$tables)) {
                continue;
            }
            $sql = str_replace(['UNIQUE INDEX UNIQUE ', 'DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL'], ['UNIQUE INDEX `UNIQUE` ', 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP'], $sql);
            $conn->exec($sql);
        }
    }

    private function insertTables(Connection $conn, $dbname)
    {
        foreach (static::$tables as $tableName) {
            if (!$this->tableExists($conn, $tableName)) {
                continue;
            }
            $columns = '`'.implode('`, `', array_map(function (Column $column) { return $column->getName(); }, $conn->getSchemaManager()->listTableColumns($tableName))).'`';
            $sql = "INSERT INTO $tableName ($columns) (SELECT $columns FROM $dbname.$tableName)";
            $conn->exec($sql);
        }
    }

    private function upgradeRefs(Connection $conn, Connection $connsbas)
    {
        foreach (static::$refsConf as $tableName => $tableData) {
            foreach ($tableData as $indice => $indiceData) {
                if (!$this->tableExists($connsbas, $tableName)) {
                    continue;
                }

                if (!$this->tableExists($conn, $tableName)) {
                    $count = 0;
                } else {
                    $sql = "SELECT MAX($indice) FROM $tableName";
                    $count = $conn->query($sql)->fetch(\PDO::FETCH_COLUMN);
                }

                $sql = "ALTER TABLE $tableName ADD  `".$indice."_BC` INT( 11 ) UNSIGNED NOT NULL";
                $connsbas->executeUpdate($sql);

                $sql = "UPDATE `$tableName` SET `".$indice."_BC` = `$indice`";
                $connsbas->executeUpdate($sql);

                $sql = "UPDATE $tableName SET $indice = $indice + ". (int) $count." WHERE $indice != 0";
                $connsbas->executeUpdate($sql);

                foreach ($indiceData as $targetTable => $columns) {
                    if (!is_array($columns)) {
                        $columns = [$columns];
                    }
                    foreach ($columns as $column) {
                        if ($this->tableExists($connsbas, $targetTable)) {

                            $sql = "ALTER TABLE `$targetTable` ADD  `".$column."_BC` INT( 11 ) UNSIGNED NOT NULL";
                            $connsbas->executeUpdate($sql);

                            $sql = "UPDATE `$targetTable` SET `".$column."_BC` = `$column`";
                            $connsbas->executeUpdate($sql);

                            $sql = "UPDATE $targetTable SET $column = $column + ". (int) $count." WHERE $column != 0";
                            $connsbas->executeUpdate($sql);
                        }
                    }
                }
            }
        }
    }

    private function downgradeRefs(Connection $connsbas)
    {
        foreach (static::$refsConf as $tableName => $tableData) {
            foreach ($tableData as $indice => $indiceData) {
                if (!$this->tableExists($connsbas, $tableName)) {
                    continue;
                }
                $sql = "UPDATE `$tableName` SET `$indice` = `".$indice."_BC`";
                $connsbas->executeUpdate($sql);

                $sql = "ALTER TABLE $tableName DROP `".$indice."_BC`";
                $connsbas->executeUpdate($sql);

                foreach ($indiceData as $targetTable => $columns) {
                    if (!is_array($columns)) {
                        $columns = [$columns];
                    }
                    foreach ($columns as $column) {
                        if ($this->tableExists($connsbas, $targetTable)) {
                            $sql = "UPDATE `$targetTable` SET `$column` = `".$column."_BC`";
                            $connsbas->executeUpdate($sql);

                            $sql = "ALTER TABLE `$targetTable` DROP  `".$column."_BC`";
                            $connsbas->executeUpdate($sql);
                        }
                    }
                }
            }
        }
    }

    private function tableExists(Connection $conn, $table)
    {
        try {
            return (Boolean) $conn->executeQuery('SHOW TABLE STATUS WHERE Name="'.$table.'" COLLATE utf8_bin')->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return false;
        }
    }

    private function tableHasField(Connection $conn, $tableName, $fieldName)
    {
        try {
            return (Boolean) $conn->executeQuery('SHOW FULL FIELDS FROM '.$tableName.' WHERE Field="'.$fieldName.'" COLLATE utf8_bin')->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return false;
        }
    }
}
