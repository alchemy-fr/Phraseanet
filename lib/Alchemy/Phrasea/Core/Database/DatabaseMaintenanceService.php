<?php

namespace Alchemy\Phrasea\Core\Database;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Setup\DoctrineMigrations\AbstractMigration;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use vierbergenlars\SemVer\version;

class DatabaseMaintenanceService
{

    private static $ormTables = [
        'AggregateTokens',
        'ApiAccounts',
        'ApiApplications',
        'ApiLogs',
        'ApiOauthCodes',
        'ApiOauthRefreshTokens',
        'ApiOauthTokens',
        'AuthFailures',
        'BasketElements',
        'BasketElementVotes',
        'BasketParticipants',
        'Baskets',
        'FeedEntries',
        'FeedItems',
        'FeedPublishers',
        'Feeds',
        'FeedTokens',
        'FtpCredential',
        'FtpExportElements',
        'FtpExports',
        'LazaretAttributes',
        'LazaretChecks',
        'LazaretFiles',
        'LazaretSessions',
        'OrderElements',
        'Orders',
        'Registrations',
        'Secrets',
        'SessionModules',
        'Sessions',
        'StoryWZ',
        'Tasks',
        'UserNotificationSettings',
        'UserQueries',
        'Users',
        'UserSettings',
        'UsrAuthProviders',
        'UsrListOwners',
        'UsrLists',
        'UsrListsContent',
    ];

    private $app;

    private $connection;

    public function __construct(Application $application, Connection $connection)
    {
        $this->app = $application;
        $this->connection = $connection;
    }

    public function upgradeDatabase(\base $base, $applyPatches, InputInterface $input, OutputInterface $output)
    {
        $dry = !!$input->getOption('dry');

        $this->reconnect();

        $recommends = [];
        $allTables = [];

        $schema = $base->get_schema();

        foreach ($schema->tables->table as $table) {
            $allTables[(string)$table['name']] = $table;
        }

        $foundTables = $this->connection->fetchAll("SHOW TABLE STATUS");

        foreach ($foundTables as $foundTable) {
            $tableName = $foundTable["Name"];

            if (isset($allTables[$tableName])) {
                $engine = strtolower(trim($allTables[$tableName]->engine));
                $ref_engine = strtolower($foundTable['Engine']);

                if ($engine != $ref_engine && in_array($engine, ['innodb', 'myisam'])) {
                    $this->alterTableEngine($tableName, $engine, $recommends);
                }

                $ret = $this->upgradeTable($allTables[$tableName]);
                $recommends = array_merge($recommends, $ret);

                unset($allTables[$tableName]);
            } elseif (!in_array($tableName, self::$ormTables)) {
                $recommends[] = [
                    'message' => 'Une table pourrait etre supprime',
                    'sql' => 'DROP TABLE ' . $base->get_dbname() . '.`' . $tableName . '`;'
                ];
            }
        }

        foreach ($allTables as $tableName => $table) {
            if($dry) {
                $output->writeln(sprintf("dry : NOT creating table \"%s\"", $tableName));
            }
            else {
                $this->createTable($table);
            }
        }

        $current_version = $base->get_version();

        if ($applyPatches) {
            $version = $this->app['phraseanet.version']->getNumber();
            $this->applyPatches(
                $base,
                $current_version,
                $version,
                false,
                $input,
                $output
            );
        }

        return $recommends;
    }

    /**
     * @param $tableName
     * @param $engine
     * @param $recommends
     * @return array
     */
    public function alterTableEngine($tableName, $engine, array & $recommends)
    {
        $this->reconnect();

        $sql = 'ALTER TABLE `' . $tableName . '` ENGINE = ' . $engine;

        try {
            $this->connection->exec($sql);
        } catch (\Exception $e) {
            $recommends[] = [
                'message' => $this->app->trans('Erreur lors de la tentative ; errreur : %message%',
                    ['%message%' => $e->getMessage()]),
                'sql' => $sql
            ];
        }
    }


    /**
     * @param  \SimpleXMLElement $table
     */
    public function createTable(\SimpleXMLElement $table)
    {
        $this->reconnect();

        $field_stmt = $defaults_stmt = [];

        $create_stmt = "CREATE TABLE IF NOT EXISTS `" . $table['name'] . "` (";

        foreach ($table->fields->field as $field) {
            $isnull = trim($field->null) == "" ? "NOT NULL" : "NULL";

            if (trim($field->default) != "" && trim($field->default) != "CURRENT_TIMESTAMP") {
                $is_default = " default '" . $field->default . "'";
            } elseif (trim($field->default) == "CURRENT_TIMESTAMP") {
                $is_default = " default " . $field->default;
            } else {
                $is_default = '';
            }

            $character_set = '';
            if (in_array(strtolower((string)$field->type), ['text', 'longtext', 'mediumtext', 'tinytext'])
                || substr(strtolower((string)$field->type), 0, 7) == 'varchar'
                || in_array(substr(strtolower((string)$field->type), 0, 4), ['char', 'enum'])
            ) {

                $collation = trim((string)$field->collation) != '' ? trim((string)$field->collation) : 'utf8_unicode_ci';

                $collations = array_reverse(explode('_', $collation));
                $code = array_pop($collations);

                $character_set = ' CHARACTER SET ' . $code . ' COLLATE ' . $collation;
            }

            $field_stmt[] = " `" . $field->name . "` " . $field->type . " "
                . $field->extra . " " . $character_set . " "
                . $is_default . " " . $isnull . "";
        }

        if ($table->indexes) {
            foreach ($table->indexes->index as $index) {
                switch ($index->type) {
                    case "PRIMARY":
                        $primary_fields = [];

                        foreach ($index->fields->field as $field) {
                            $primary_fields[] = "`" . $field . "`";
                        }

                        $field_stmt[] = 'PRIMARY KEY (' . implode(',', $primary_fields) . ')';
                        break;
                    case "UNIQUE":
                        $unique_fields = [];

                        foreach ($index->fields->field as $field) {
                            $unique_fields[] = "`" . $field . "`";
                        }

                        $field_stmt[] = 'UNIQUE KEY `' . $index->name . '` (' . implode(',', $unique_fields) . ')';
                        break;
                    case "INDEX":
                        $index_fields = [];

                        foreach ($index->fields->field as $field) {
                            $index_fields[] = "`" . $field . "`";
                        }

                        $field_stmt[] = 'KEY `' . $index->name . '` (' . implode(',', $index_fields) . ')';
                        break;
                }
            }
        }

        if ($table->defaults) {
            foreach ($table->defaults->default as $default) {
                $params = $dates_values = [];
                $nonce = $this->app['random.medium']->generateString(16);

                foreach ($default->data as $data) {
                    $k = trim($data['key']);

                    if ($k === 'usr_password') {
                        $data = $this->app['auth.password-encoder']->encodePassword($data, $nonce);
                    }

                    if ($k === 'nonce') {
                        $data = $nonce;
                    }

                    $v = trim(str_replace(["\r\n", "\r", "\n", "\t"], '', $data));

                    if (trim(mb_strtolower($v)) == 'now()') {
                        $dates_values [$k] = 'NOW()';
                    } else {
                        $params[$k] = (trim(mb_strtolower($v)) == 'null' ? null : $v);
                    }
                }

                $separator = ((count($params) > 0 && count($dates_values) > 0) ? ', ' : '');

                $defaults_stmt[] = [
                    'sql' =>
                        'INSERT INTO `' . $table['name'] . '` (' . implode(', ', array_keys($params))
                        . $separator . implode(', ', array_keys($dates_values)) . ')
                      VALUES (:' . implode(', :', array_keys($params))
                        . $separator . implode(', ', array_values($dates_values)) . ') '
                    ,
                    'params' => $params
                ];
            }
        }

        $engine = mb_strtolower(trim($table->engine));
        $engine = in_array($engine, ['innodb', 'myisam']) ? $engine : 'innodb';

        $create_stmt .= implode(',', $field_stmt);
        $create_stmt .= ") ENGINE=" . $engine . " CHARACTER SET utf8 COLLATE utf8_unicode_ci;";

        $this->connection->exec($create_stmt);

        foreach ($defaults_stmt as $def) {
            $stmt = $this->connection->prepare($def['sql']);
            $stmt->execute($def['params']);
        }

        unset($stmt);
    }

    public function upgradeTable(\SimpleXMLElement $table)
    {
        $this->reconnect();

        $correct_table = ['fields' => [], 'indexes' => [], 'collation' => []];
        $alter = $alter_pre = $return = [];

        foreach ($table->fields->field as $field) {
            $expr = trim((string)$field->type);

            $_extra = trim((string)$field->extra);
            if ($_extra) {
                $expr .= ' ' . $_extra;
            }

            $collation = trim((string)$field->collation) != '' ? trim((string)$field->collation) : 'utf8_unicode_ci';

            if (in_array(strtolower((string)$field->type), ['text', 'longtext', 'mediumtext', 'tinytext'])
                || substr(strtolower((string)$field->type), 0, 7) == 'varchar'
                || in_array(substr(strtolower((string)$field->type), 0, 4), ['char', 'enum'])
            ) {
                $collations = array_reverse(explode('_', $collation));
                $code = array_pop($collations);

                $collation = ' CHARACTER SET ' . $code . ' COLLATE ' . $collation;

                $correct_table['collation'][trim((string)$field->name)] = $collation;

                $expr .= $collation;
            }

            $_null = mb_strtolower(trim((string)$field->null));
            if (!$_null || $_null == 'no') {
                $expr .= ' NOT NULL';
            }

            $_default = (string)$field->default;
            if ($_default && $_default != 'CURRENT_TIMESTAMP') {
                $expr .= ' DEFAULT \'' . $_default . '\'';
            } elseif ($_default == 'CURRENT_TIMESTAMP') {
                $expr .= ' DEFAULT ' . $_default . '';
            }

            $correct_table['fields'][trim((string)$field->name)] = $expr;
        }
        if ($table->indexes) {
            foreach ($table->indexes->index as $index) {
                $i_name = (string)$index->name;
                $expr = [];
                foreach ($index->fields->field as $field) {
                    $expr[] = '`' . trim((string)$field) . '`';
                }

                $expr = implode(', ', $expr);

                switch ((string)$index->type) {
                    case "PRIMARY":
                        $correct_table['indexes']['PRIMARY'] = 'PRIMARY KEY (' . $expr . ')';
                        break;
                    case "UNIQUE":
                        $correct_table['indexes'][$i_name] = 'UNIQUE KEY `' . $i_name . '` (' . $expr . ')';
                        break;
                    case "INDEX":
                        $correct_table['indexes'][$i_name] = 'KEY `' . $i_name . '` (' . $expr . ')';
                        break;
                }
            }
        }

        $sql = "SHOW FULL FIELDS FROM `" . $table['name'] . "`";
        $rs2 = $this->connection->fetchAll($sql);

        foreach ($rs2 as $row2) {
            $f_name = $row2['Field'];
            $expr_found = trim($row2['Type']);

            $_extra = $row2['Extra'];

            if ($_extra) {
                $expr_found .= ' ' . $_extra;
            }

            $_collation = $row2['Collation'];

            $current_collation = '';

            if ($_collation) {
                $_collation = explode('_', $row2['Collation']);

                $expr_found .= $current_collation = ' CHARACTER SET ' . $_collation[0] . ' COLLATE ' . implode('_',
                        $_collation);
            }

            $_null = mb_strtolower(trim($row2['Null']));

            if (!$_null || $_null == 'no') {
                $expr_found .= ' NOT NULL';
            }

            $_default = $row2['Default'];

            if ($_default) {
                if (trim($row2['Type']) == 'timestamp' && $_default == 'CURRENT_TIMESTAMP') {
                    $expr_found .= ' DEFAULT CURRENT_TIMESTAMP';
                } else {
                    $expr_found .= ' DEFAULT \'' . $_default . '\'';
                }
            }

            if (isset($correct_table['fields'][$f_name])) {
                if (isset($correct_table['collation'][$f_name]) && $correct_table['collation'][$f_name] != $current_collation) {
                    $old_type = mb_strtolower(trim($row2['Type']));
                    $new_type = false;

                    switch ($old_type) {
                        case 'text':
                            $new_type = 'blob';
                            break;
                        case 'longtext':
                            $new_type = 'longblob';
                            break;
                        case 'mediumtext':
                            $new_type = 'mediumblob';
                            break;
                        case 'tinytext':
                            $new_type = 'tinyblob';
                            break;
                        default:
                            if (substr($old_type, 0, 4) == 'char') {
                                $new_type = 'varbinary(255)';
                            }
                            if (substr($old_type, 0, 7) == 'varchar') {
                                $new_type = 'varbinary(767)';
                            }
                            break;
                    }

                    if ($new_type) {
                        $alter_pre[] = "ALTER TABLE `" . $table['name'] . "` CHANGE `$f_name` `$f_name` " . $new_type . "";
                    }
                }

                if (strtolower($expr_found) !== strtolower($correct_table['fields'][$f_name])) {
                    $alter[] = "ALTER TABLE `" . $table['name'] . "` CHANGE `$f_name` `$f_name` " . $correct_table['fields'][$f_name];
                }
                unset($correct_table['fields'][$f_name]);
            } else {
                $return[] = [
                    'message' => 'Un champ pourrait etre supprime',
                    'sql' => "ALTER TABLE " . $this->connection->getDatabase() . ".`" . $table['name'] . "` DROP `$f_name`;"
                ];
            }
        }

        foreach ($correct_table['fields'] as $f_name => $expr) {
            $alter[] = "ALTER TABLE `" . $table['name'] . "` ADD `$f_name` " . $correct_table['fields'][$f_name];
        }

        $tIndex = [];
        $sql = "SHOW INDEXES FROM `" . $table['name'] . "`";
        $rs2 = $this->connection->fetchAll($sql);

        foreach ($rs2 as $row2) {
            if (!isset($tIndex[$row2['Key_name']])) {
                $tIndex[$row2['Key_name']] = ['unique' => ((int)($row2['Non_unique']) == 0), 'columns' => []];
            }
            $tIndex[$row2['Key_name']]['columns'][(int)($row2['Seq_in_index'])] = $row2['Column_name'];
        }

        foreach ($tIndex as $kIndex => $vIndex) {
            $strColumns = [];

            foreach ($vIndex['columns'] as $column) {
                $strColumns[] = '`' . $column . '`';
            }

            $strColumns = '(' . implode(', ', $strColumns) . ')';

            if ($kIndex == 'PRIMARY') {
                $expr_found = 'PRIMARY KEY ' . $strColumns;
            } else {
                if ($vIndex['unique']) {
                    $expr_found = 'UNIQUE KEY `' . $kIndex . '` ' . $strColumns;
                } else {
                    $expr_found = 'KEY `' . $kIndex . '` ' . $strColumns;
                }
            }

            $full_name_index = ($kIndex == 'PRIMARY') ? 'PRIMARY KEY' : ('INDEX `' . $kIndex . '`');

            if (isset($correct_table['indexes'][$kIndex])) {

                if (mb_strtolower($expr_found) !== mb_strtolower($correct_table['indexes'][$kIndex])) {
                    $alter[] = 'ALTER TABLE `' . $table['name'] . '` DROP ' . $full_name_index . ', ADD ' . $correct_table['indexes'][$kIndex];
                }

                unset($correct_table['indexes'][$kIndex]);
            } else {
                $return[] = [
                    'message' => 'Un index pourrait etre supprime',
                    'sql' => 'ALTER TABLE ' . $this->connection->getDatabase() . '.`' . $table['name'] . '` DROP ' . $full_name_index . ';'
                ];
            }
        }

        foreach ($correct_table['indexes'] as $kIndex => $expr) {
            $alter[] = 'ALTER TABLE `' . $table['name'] . '` ADD ' . $expr;
        }

        foreach ($alter_pre as $a) {
            $this->reconnect();

            try {
                $this->connection->exec($a);
            } catch (\Exception $e) {
                $return[] = [
                    'message' => $this->app->trans('Erreur lors de la tentative ; errreur : %message%',
                        ['%message%' => $e->getMessage()]),
                    'sql' => $a
                ];
            }
        }

        foreach ($alter as $a) {
            $this->reconnect();

            try {
                $this->connection->exec($a);
            } catch (\Exception $e) {
                $return[] = [
                    'message' => $this->app->trans('Erreur lors de la tentative ; errreur : %message%',
                        ['%message%' => $e->getMessage()]),
                    'sql' => $a
                ];
            }
        }

        return $return;
    }

    public function applyPatches(\base $base, $from, $to, $post_process, InputInterface $input, OutputInterface $output)
    {
        $dry = !!$input->getOption('dry');

        $output->writeln(sprintf("into applyPatches(from=%s, to=%s, post_process=%s) on base \"%s\"", $from, $to, $post_process?'true':'false', $base->get_dbname()));

        if (version::eq($from, $to)) {
            return true;
        }

        $list_patches = [];

        $iterator = new \DirectoryIterator($this->app['root.path'] . '/lib/classes/patch/');

        foreach ($iterator as $fileinfo) {
            if (!$fileinfo->isDot()) {
// printf("---- [%d]\n", __LINE__);
                if (substr($fileinfo->getFilename(), 0, 1) == '.') {
                    continue;
                }
                $versions = array_reverse(explode('.', $fileinfo->getFilename()));
                $classname = 'patch_' . array_pop($versions);

                /** @var \patchAbstract $patch */
                $patch = new $classname();

// printf("---- [%d]\n", __LINE__);
                if (!in_array($base->get_base_type(), $patch->concern())) {
                    continue;
                }

                if (!!$post_process !== !!$patch->require_all_upgrades()) {
                    continue;
                }

// printf("---- [%d] %s ; from: %s ; patch: %s; to:%s\n", __LINE__, $classname, $from, $patch->get_release(), $to);
// printf("---- [%d]\n", __LINE__);
                // if patch is older than current install
                if (version::lte($patch->get_release(), $from)) {
                    continue;
                }
// printf("---- [%d]\n", __LINE__);
                // if patch is new than current target
                if (version::gt($patch->get_release(), $to)) {
                    continue;
                }
// printf("---- [%d]\n", __LINE__);

                $n = 0;
                do {
                    $key = $patch->get_release() . '.' . $n;
                    $n++;
                } while (isset($list_patches[$key]));

                $list_patches[$key] = $patch;
            }
        }

        uasort($list_patches, function (\patchInterface $patch1, \patchInterface $patch2) {
            return version::lt($patch1->get_release(), $patch2->get_release()) ? -1 : 1;
        });

        $success = true;

        // disable mail
        $this->app['swiftmailer.transport'] = null;
// var_dump($list_patches);
        foreach ($list_patches as $patch) {

            $output->writeln(sprintf(" - patch \"%s\" (release %s) should be applied", get_class($patch), $patch->get_release()));
            // Gets doctrine migrations required for current patch
            foreach ($patch->getDoctrineMigrations() as $doctrineVersion) {
                /** @var \Doctrine\DBAL\Migrations\Version $version */
                $version = $this->app['doctrine-migration.configuration']->getVersion($doctrineVersion);
                // Skip if already migrated
                if ($version->isMigrated()) {
                    continue;
                }

                $migration = $version->getMigration();

                // Handle legacy migrations
                if ($migration instanceof AbstractMigration) {
                    // Inject entity manager
                    $migration->setEntityManager($this->app['orm.em']);

                    // Execute migration if not marked as migrated and not already applied by an older patch
                    if (!$migration->isAlreadyApplied()) {
                        if($dry) {
                            $output->writeln(sprintf("    dry : NOT executing(up) legacy migration \"%s\"", get_class($migration)));
                        }
                        else {
                            $output->writeln(sprintf("    executing(up) legacy migration \"%s\"", get_class($migration)));
                            $this->reconnect();
                            $version->execute('up');
                        }
                        continue;
                    }

                    // Or mark it as migrated
                    if($dry) {
                        $output->writeln(sprintf("    dry : NOT marking migrated legacy migration \"%s\"", get_class($migration)));
                    }
                    else {
                        $output->writeln(sprintf("    marking migrated legacy migration \"%s\"", get_class($migration)));
                        $version->markMigrated();
                    }
                }
                else {
                    if($dry) {
                        $output->writeln(sprintf("    dry : NOT executing(up) doctrine migration \"%s\"", get_class($migration)));
                    }
                    else {
                        $output->writeln(sprintf("    executing(up) migration doctrine \"%s\"", get_class($migration)));
                        $this->reconnect();
                        $version->execute('up');
                    }
                }
            }

            $this->reconnect();

            if($dry) {
                $output->writeln(sprintf("    dry : NOT applying patch \"%s\"", get_class($patch)));
            }
            else {
                $output->writeln(sprintf("    applying patch \"%s\"", get_class($patch)));
                if (false === $patch->apply($base, $this->app)) {
                    $success = false;
                }
            }
        }

        return $success;
    }

    private function reconnect()
    {
        if($this->connection->ping() === false) {
            $this->connection->close();
            $this->connection->connect();
        }
    }
}
