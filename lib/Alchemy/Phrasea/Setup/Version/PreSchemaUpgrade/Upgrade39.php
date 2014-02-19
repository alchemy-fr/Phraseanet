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
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Exception\RuntimeException;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;
use Gedmo\Timestampable\TimestampableListener;
use Symfony\Component\Console\Helper\TableHelper;
use Symfony\Component\Console\Output\ConsoleOutput;

class Upgrade39 implements PreSchemaUpgradeInterface
{
    private static $batchSize = 100;

    private $backupFeeds = false;
    private $migrateUsers = false;
    private $tableNames;

    /**
     * Returns the corresponding user entity from old user id in 'usr' table.
     *
     * @param EntityManager $em
     * @param               $id
     *
     * @return mixed
     * @throws RuntimeException
     */
    public static function getUserReferences(EntityManager $em, $id)
    {
        $q = $em->createQuery('SELECT PARTIAL u.{id,login} FROM Phraseanet:User u WHERE u.id = :id');
        $q->setParameters(['id' => $id]);
        $q->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);

        return $q->getSingleResult();
    }

    /**
     * {@inheritdoc}
     */
    public function apply(EntityManager $em, \appbox $appbox, Configuration $conf)
    {
        // Backup Feeds table
        $this->doBackupFeedsTable($em);
        try {
            // Migrate User table
            $this->doUsersMigration($em, $appbox->get_connection(), $conf);
        } catch (\Exception $e) {
            echo $e->getMessage();
            echo "\nAn error occured.\nPlease wait while reverting changes ...\n";
            $this->rollbackMigration($em, $conf);
            exit(1);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isApplyable(Application $app)
    {
        $rs = $app['EM']->createNativeQuery(
            'SHOW TABLE STATUS', (new ResultSetMapping())->addScalarResult('Name', 'Name')
        )->getResult();

        foreach ($rs as $row) {
            if ('feeds' === $row['Name']) {
                $this->backupFeeds = true;
                break;
            }
        }

        $userTableExists = false;
        foreach ($rs as $row) {
            if ('Users' === $row['Name']) {
                $userTableExists = true;
                break;
            }
        }

        $this->migrateUsers = !$userTableExists;

        return $this->backupFeeds || $this->migrateUsers;
    }

    /**
     * Executes user migration.
     *
     * @param EntityManager $em
     * @param \PDO          $conn
     * @param Configuration $conf
     */
    private function doUsersMigration(EntityManager $em, \PDO $conn, Configuration $conf)
    {
        if (!$this->migrateUsers) {
            return;
        }
        $this->tableNames = $em->getConnection()->getSchemaManager()->listTableNames();

        // Sanitize usr table
        $this->sanitizeUsrTable($em);
        // Creates User schema
        $version = $conf->getVersion('user');
        if (false === $version->isMigrated()) {
            $version->execute('up');
        }
        // Creates user entities
        $this->migrateUsers($em, $conn);
        // Creates user mapping table
        $this->createMigrationTable($em);
        // checks if migration is ok
        $this->checkMigration($em);
        // Creates user model references
        $this->migrateModels($em, $conn);
        // Renames usr_id columns to user_id
        $this->renameUserFields($em);
        // Replace ids created by doctrine with old ids
        $this->updateUserIds($em);
    }

    /**
     * Rollback migration to origin state.
     *
     * @param EntityManager $em
     * @param Configuration $conf
     */
    private function rollbackMigration(EntityManager $em,Configuration $conf)
    {
        // rename fields
        $this->renameUserFields($em, 'down');
        // truncate created tables
        $this->emptyTables($em);
        // rollback schema
        $version = $conf->getVersion('user');
        if ($version->isMigrated()) {
            $version->execute('down');
        }
    }

    /**
     * Empty User & migration table.
     *
     * @param EntityManager $em
     */
    private function emptyTables(EntityManager $em)
    {
        $meta = $em->getClassMetadata('Alchemy\Phrasea\Model\Entities\User');
        $connection = $em->getConnection();
        $dbPlatform = $connection->getDatabasePlatform();
        $connection->beginTransaction();
        try {
            $connection->query('SET FOREIGN_KEY_CHECKS=0');
            $connection->executeUpdate($dbPlatform->getTruncateTableSql('user_migration_mapping'));
            $connection->executeUpdate($dbPlatform->getTruncateTableSql($meta->getTableName()));
            $connection->query('SET FOREIGN_KEY_CHECKS=1');
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollback();
        }
    }

    /**
     * Fix character set in usr table.
     *
     * @param $em
     */
    private function sanitizeUsrTable($em)
    {
        $rs = $em->createNativeQuery(
            "SHOW FIELDS FROM usr WHERE Field = 'usr_login';",
            (new ResultSetMapping())->addScalarResult('Type', 'Type')
        )->getSingleResult();

        if (0 !== strpos(strtolower($rs['Type']), 'varbinary')) {
            return;
        }

        // As 'usr_login' field type is varbinary it can contain any charset (utf8 or latin1).
        // Compare usr_login to usr_login converted to utf8>utf32>utf8 will detect broken char for latin1 encoded string.
        // Detected 'usr_login' fields  must be updated using CONVERT(CAST(usr_login AS CHAR CHARACTER SET latin1) USING utf8)
        $rs = $em->createNativeQuery(
            'SELECT t.usr_id, t.login_utf8 FROM (
                SELECT usr_id,
                usr_login AS login_unknown_charset,
                CONVERT(CAST(usr_login AS CHAR CHARACTER SET latin1) USING utf8) login_utf8,
                CONVERT(CONVERT(CAST(usr_login AS CHAR CHARACTER SET utf8) USING utf32) USING utf8) AS login_utf8_utf32_utf8
                FROM usr
            ) AS t
            WHERE t.login_utf8_utf32_utf8 != t.login_unknown_charset',
            (new ResultSetMapping())
                ->addScalarResult('usr_id', 'usr_id')
                ->addScalarResult('login_utf8', 'login_utf8')
        )->getResult();

        foreach ($rs as $row) {
            $em->getConnection()->executeQuery(sprintf('UPDATE usr SET usr_login="%s" WHERE usr_id=%d', $row['login_utf8'], $row['usr_id']));
        }

        foreach ([
            // drop index
            "ALTER TABLE usr DROP INDEX usr_login;",
            // change field type
            "ALTER TABLE usr MODIFY usr_login VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_bin;",
            // recreate index
            "CREATE UNIQUE INDEX usr_login ON usr (usr_login);"
        ] as $sql) {
            $em->getConnection()->executeQuery($sql);
        }
    }

    /**
     * Renames all usr_id property from entities to user_id.
     *
     * @param EntityManager $em
     */
    private function renameUserFields(EntityManager $em, $direction = 'up')
    {
        $tables = [
            'Baskets',
            'LazaretSessions',
            'Sessions',
            'StoryWZ',
            'UsrAuthProviders',
            'UsrListOwners',
            'UsrListsContent',
            'ValidationParticipants',
        ];

        $sql = 'ALTER TABLE %s CHANGE '.($direction === 'up' ? 'usr_id user_id' : 'user_id usr_id').' INT';
        foreach ($tables as $tableName) {
            if (false === $this->tableExists($tableName)) {
                continue;
            }
            $em->getConnection()->executeQuery(sprintf($sql, $tableName));
        }
    }

    /**
     * Renames feed table.
     *
     * @param EntityManager $em
     */
    private function doBackupFeedsTable(EntityManager $em)
    {
        if (!$this->backupFeeds) {
            return;
        }
        $em->getConnection()->executeQuery('RENAME TABLE `feeds` TO `feeds_backup`');
    }

    /**
     * Checks whether all user have been migrated.
     *
     * @param EntityManager $em
     *
     * @throws RuntimeException
     */
    private function checkMigration(EntityManager $em)
    {
        $users = $em->createNativeQuery('
            SELECT usr.usr_id AS id, usr.usr_login AS login, usr.usr_mail AS email
            FROM usr
            WHERE usr.usr_id NOT IN (
                SELECT old_usr_id
                FROM user_migration_mapping
            )', (new ResultSetMapping())->addScalarResult('login', 'login')->addScalarResult('id', 'id')
        )->getResult();

        if (count($users) > 0) {
            $table = new TableHelper();
            $table
                ->setHeaders(['id', 'login', 'email'])
                ->setRows($users);

            throw new RuntimeException("Some users could not be migrated \n" . $table->render(new ConsoleOutput()));
        }
    }

    /**
     * Creates migration table which map olf user_id with the new ones.
     *
     * @param EntityManager $em
     */
    private function createMigrationTable(EntityManager $em)
    {
        $em->getConnection()->executeQuery('DROP TABLE IF EXISTS user_migration_mapping');
        $em->getConnection()->executeQuery('CREATE TABLE IF NOT EXISTS user_migration_mapping (
            old_usr_id INT UNSIGNED NOT NULL PRIMARY KEY,
            new_usr_id INT UNSIGNED NOT NULL
        )
        SELECT usr.usr_id AS old_usr_id, Users.id AS new_usr_id
        FROM usr INNER JOIN Users ON (Users.login = usr.usr_login)');
    }

    /**
     * Migrates Users to doctrine entity.
     *
     * @param EntityManager $em
     * @param \PDO          $conn
     */
    private function migrateUsers(EntityManager $em, \PDO $conn)
    {
        $em->getEventManager()->removeEventSubscriber(new TimestampableListener());
        $this->updateUsers($em, $conn);
        $em->getEventManager()->addEventSubscriber(new TimestampableListener());
    }

    /**
     * Migrates models to doctrine entity.
     *
     * @param EntityManager $em
     */
    private function migrateModels(EntityManager $em)
    {
        $em->getEventManager()->removeEventSubscriber(new TimestampableListener());
        $this->updateModels($em);
        $em->getEventManager()->addEventSubscriber(new TimestampableListener());
    }

    /**
     * Checks whether the table exists or not.
     *
     * @param $tableName
     *
     * @return boolean
     */
    private function tableExists($tableName)
    {
        return in_array($tableName, $this->tableNames);
    }

    /**
     * Check whether the usr table has a nonce column or not.
     *
     * @param EntityManager $em
     *
     * @return boolean
     */
    private function hasNonceColumn(EntityManager $em)
    {
        return (Boolean) $em->createNativeQuery(
            "SHOW FIELDS FROM usr WHERE Field = 'nonce';",
            new ResultSetMapping()
        )->getOneOrNullResult();
    }

    /**
     * Sets user entity from usr table.
     */
    private function updateUsers(EntityManager $em, \PDO $conn)
    {
        if ($this->hasNonceColumn($em)) {
            $sql = 'SELECT activite, adresse, create_db, canchgftpprofil, canchgprofil, ville,
                    societe, pays, usr_mail, fax, usr_prenom, geonameid, invite, fonction,
                    last_conn, lastModel, usr_nom, ldap_created, locale, usr_login,
                    mail_notifications, nonce, usr_password, push_list, mail_locked,
                    request_notifications, salted_password, usr_sexe, tel, timezone, cpostal, usr_creationdate,
                    usr_modificationdate
                FROM usr';
        } else {
            $sql = 'SELECT activite, adresse, create_db, canchgftpprofil, canchgprofil, ville,
                    societe, pays, usr_mail, fax, usr_prenom, geonameid, invite, fonction,
                    last_conn, lastModel, usr_nom, ldap_created, locale, usr_login,
                    mail_notifications, NULL as nonce, usr_password, push_list, mail_locked,
                    request_notifications, "0" as salted_password, usr_sexe, tel, timezone, cpostal, usr_creationdate,
                    usr_modificationdate
                FROM usr';
        }

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $n = 0;
        foreach ($rows as $row) {
            $user = new User();
            $user->setActivity($row['activite']);
            $user->setAddress($row['adresse']);
            $user->setAdmin((Boolean) $row['create_db']);
            $user->setCanChangeFtpProfil((Boolean) $row['canchgftpprofil']);
            $user->setCanChangeProfil((Boolean) $row['canchgprofil']);
            $user->setCity($row['ville']);
            $user->setCompany($row['societe']);
            $user->setCountry((string) $row['pays']);
            $user->setEmail($row['usr_mail']);
            $user->setFax($row['fax']);
            $user->setFirstName($row['usr_prenom']);
            if ($row['geonameid'] > 0) {
                $user->setGeonameId($row['geonameid']);
            }
            $user->setGuest((Boolean) $row['invite']);
            $user->setJob($row['fonction']);
            $user->setLastConnection(new \DateTime($row['last_conn']));
            $user->setLastName($row['usr_nom']);
            $user->setLdapCreated((Boolean) $row['ldap_created']);
            try {
                $user->setLocale($row['locale']);
            } catch (\InvalidArgumentException $e ) {

            }

            $user->setLogin($row['usr_login']);

            if (substr($row['usr_login'], 0, 10) === '(#deleted_') {
                $user->setDeleted(true);
            }

            $user->setMailLocked((Boolean) $row['mail_locked']);
            $user->setMailNotificationsActivated((Boolean) $row['mail_notifications']);
            $user->setNonce($row['nonce']);
            $user->setPassword($row['usr_password']);
            $user->setPushList($row['push_list']);
            $user->setRequestNotificationsActivated((Boolean) $row['request_notifications']);
            $user->setSaltedPassword((Boolean) $row['salted_password']);

            switch ($row['usr_sexe']) {
                case 0:
                    $gender = User::GENDER_MISS;
                    break;
                case 1:
                    $gender = User::GENDER_MRS;
                    break;
                case 2:
                    $gender = User::GENDER_MR;
                    break;
                default:
                    $gender = null;
            }

            $user->setGender($gender);
            $user->setPhone($row['tel']);
            $user->setTimezone($row['timezone']);
            $user->setZipCode($row['cpostal']);
            $user->setCreated(new \DateTime($row['usr_creationdate']));
            $user->setupdated(new \DateTime($row['usr_modificationdate']));

            $em->persist($user);

            $n++;
            if ($n % self::$batchSize === 0) {
                $em->flush();
                $em->clear();
            }
        }

        $em->flush();
        $em->clear();
    }

    /**
     * Sets last_model from usr table.
     */
    private function updateLastModels(EntityManager $em)
    {
        $n = 0;
        foreach ($em->createNativeQuery(
             "SELECT lastModel AS last_model, usr_id FROM usr WHERE lastModel > 0",
             (new ResultSetMapping())->addScalarResult('last_model', 'last_model')->addScalarResult('usr_id', 'usr_id')
         )->getResult() as $row) {
            $user = self::getUserReferences($em, $row['usr_id']);

            try {
                $lastModel = self::getUserReferences($em, $row['last_model']);
            } catch (NoResultException $e) {
               continue;
            }

            if (false === $lastModel->isTemplate()) {
                continue;
            }

            $user->setLastModel($lastModel);

            $em->persist($user);

            $n++;
            if ($n % self::$batchSize === 0) {
                $em->flush();
                $em->clear();
            }
        }

        $em->flush();
        $em->clear();
    }

    /**
     * Sets model from usr table.
     */
    private function updateModels(EntityManager $em)
    {
        $n = 0;
        foreach ($em->createNativeQuery(
             "SELECT model_of, usr_id FROM usr WHERE model_of > 0",
             (new ResultSetMapping())->addScalarResult('model_of', 'model_of')->addScalarResult('usr_id', 'usr_id')
         )->getResult() as $row) {
            $template = self::getUserReferences($em, $row['usr_id']);
            try {
                $owner = self::getUserReferences($em, $row['model_of']);
                $template->setModelOf($owner);
                $em->persist($owner);
            } catch (NoResultException $e) {
                // remove template with no owner
                $em->remove($template);
            }

            $n++;
            if ($n % self::$batchSize === 0) {
                $em->flush();
                $em->clear();
            }
        }
        $em->flush();
        $em->clear();

        $this->updateLastModels($em);
    }

    /**
     * Replaces the new doctrine user entity id by the old ones.
     *
     * @param EntityManager $em
     *
     * @throws \Exception
     */
    private function updateUserIds(EntityManager $em)
    {
        $sqlPreUpdate = $sqlPostUpdate = $sqlUpdate = [];
        $connection = $em->getConnection();
        $dbPlatform = $connection->getDatabasePlatform();
        $schemaManager = $connection->getSchemaManager();

        // Sets primary key
        $sqlPostUpdate[] = 'ALTER TABLE Users ADD PRIMARY KEY (`id`)';

        foreach ($schemaManager->listTableColumns('Users') as $column) {
            if ($column->getName() === 'id') {
                // Sets auto increment
                $sqlPostUpdate[] = 'ALTER TABLE Users MODIFY ' . $dbPlatform->getColumnDeclarationSQL($column->getQuotedName($dbPlatform), $column->toArray());
                $column->setAutoincrement(false);
                // Remove auto increment
                $sqlPreUpdate[] = 'ALTER TABLE Users MODIFY ' . $dbPlatform->getColumnDeclarationSQL($column->getQuotedName($dbPlatform), $column->toArray());
                break;
            }
        }
        // Remove FK
        foreach ($schemaManager->listTableForeignKeys('Users') as $fk) {
            $cols = $fk->getColumns();
            $fcols = $fk->getForeignColumns();
            if ((count($cols) === 1 && in_array('model_of', $cols)) && (count($fcols) === 1 && in_array('id', $fcols))) {
                $sqlPreUpdate[] = $dbPlatform->getDropForeignKeySQL($fk, 'Users');
                $sqlPostUpdate[] = $dbPlatform->getCreateForeignKeySQL($fk, 'Users');
                break;
            }
        }
        // Drop PK
        $sqlPreUpdate[] = 'ALTER TABLE Users DROP PRIMARY KEY';
        // Update ids value using embedded SQL
        $sqlUpdate[] = 'UPDATE Users u JOIN user_migration_mapping m ON u.id = m.new_usr_id SET u.id = m.old_usr_id';
        $sqlUpdate[] = 'UPDATE Users u JOIN user_migration_mapping m ON u.model_of = m.new_usr_id SET u.model_of = m.old_usr_id';
        // Sets proper value for autoincrement
        $maxId = $em->createNativeQuery('SELECT MAX(usr_id) as max_id FROM usr', (new ResultSetMapping())->addScalarResult('max_id', 'max_id'))->getSingleScalarResult();
        $sqlPostUpdate[] = 'ALTER TABLE Users AUTO_INCREMENT='.$maxId;
        // executes SQLS
        $connection->beginTransaction();
        try {
            foreach (array_merge($sqlPreUpdate, $sqlUpdate, $sqlPostUpdate) as $sql) {
                $connection->executeQuery($sql);
            }
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollback();
            throw $e;
        }
    }
}
