<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
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
    private $backupFeeds = false;
    private $migrateUsers = false;

    private $tablesStatus;

    private static $users = [];

    /**
     * Returns the corresponding user entity from old user id in 'usr' table.
     *
     * @param EntityManager $em
     * @param               $oldId
     * @param bool          $fromCache
     *
     * @return mixed
     * @throws RuntimeException
     */
    public static function getUserFromOldId(EntityManager $em, $oldId, $fromCache = true)
    {
        if ($fromCache && array_key_exists($oldId, self::$users)) {
            return self::$users[$oldId];
        }

        try {
            $id = $em->createNativeQuery(
                sprintf('SELECT new_usr_id FROM user_migration_mapping WHERE old_usr_id = %d', $oldId), (new ResultSetMapping())->addScalarResult('new_usr_id', 'new_usr_id')
            )->getSingleScalarResult();
        } catch (NoResultException $e) {
            throw new RuntimeException(sprintf('Old user id `%d` could not be found from user_migration_mapping', $oldId), null, $e);
        }

        $q = $em->createQuery('SELECT PARTIAL u.{id,login} FROM Alchemy\Phrasea\Model\Entities\User u WHERE u.id = :id');
        $q->setParameters(['id' => $id]);
        $q->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);

        return self::$users[$oldId] = $q->getSingleResult();
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
        // Updates user references
        $this->updateUserReferences($em);
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
     * Updates all foreign key references to old user id with the new one.
     *
     * @param EntityManager $em
     */
    private function updateUserReferences(EntityManager $em)
    {
        $tables = [
            'Baskets' => [['to_update' => 'user_id', 'primary' => 'id'], ['to_update' => 'pusher_id', 'primary' => 'id']],
            'LazaretSessions' => [['to_update' => 'user_id', 'primary' => 'id']],
            'Sessions' => [['to_update' => 'user_id', 'primary' => 'id']],
            'StoryWZ' => [['to_update' => 'user_id', 'primary' => 'id']],
            'UsrListOwners' => [['to_update' => 'user_id', 'primary' => 'id']],
            'UsrAuthProviders' => [['to_update' => 'user_id', 'primary' => 'id']],
            'UsrListsContent' => [['to_update' => 'user_id', 'primary' => 'id']],
            'ValidationParticipants' => [['to_update' => 'user_id', 'primary' => 'id']],
            'api_accounts' => [['to_update' => 'usr_id', 'primary' => 'api_account_id']],
            'basusr' => [['to_update' => 'usr_id', 'primary' => 'id']],
            'bridge_accounts' => [['to_update' => 'usr_id', 'primary' => 'id']],
            'sbasusr' => [['to_update' => 'usr_id', 'primary' => 'sbasusr_id']],
            'demand' => [['to_update' => 'usr_id', 'primary' => ['usr_id', 'base_id', 'en_cours']]],
            'edit_presets' => [['to_update' => 'usr_id', 'primary' => 'edit_preset_id']],
            'notifications' => [['to_update' => 'usr_id', 'primary' => 'id']],
            'ValidationSessions' => [['to_update' => 'initiator_id', 'primary' => 'id']],
        ];

        // drop indexes where 'user_id' field is part of the index
        $this->indexes($em, 'drop');

        // start transaction to be sure that references update went ok
        $em->getConnection()->beginTransaction();
        try {
            foreach ($tables as $tableName => $fields) {
                if (false === $this->tableExists($em, $tableName)) {
                    continue;
                }
                $this->doUpdateFields($em, $tableName, $fields);
            }
            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollback();
            throw $e;
        }
        // restore indexes
        $this->indexes($em, 'restore');
    }

    /**
     * Drops or restores indexes that contains a user id field.
     *
     * @param EntityManager $em
     * @param               $action
     */
    private function indexes(EntityManager $em, $action)
    {
        if ($action === 'drop') {
            if ($this->tableExists($em, 'demand') && $this->indexExists($em, 'demand', 'PRIMARY')) {
                $em->getConnection()->executeQuery('ALTER TABLE demand DROP PRIMARY KEY');
            }

            if ($this->tableExists($em, 'UsrListsContent') && $this->indexExists($em, 'UsrListsContent', 'unique_usr_per_list')) {
                $em->getConnection()->executeQuery('ALTER TABLE UsrListsContent DROP INDEX `unique_usr_per_list`');
            }
        }

        if ($action === 'restore') {
            if ($this->tableExists($em, 'demand') && !$this->indexExists($em, 'demand', 'PRIMARY')) {
                $em->getConnection()->executeQuery('ALTER TABLE demand ADD PRIMARY KEY (`usr_id`, `base_id`, `en_cours`);');
            }

            if ($this->tableExists($em, 'UsrListsContent') && !$this->indexExists($em, 'UsrListsContent', 'unique_usr_per_list')) {
                $em->getConnection()->executeQuery('ALTER TABLE UsrListsContent ADD INDEX `unique_usr_per_list` (`user_id`, `list_id`);');
            }
        }
    }

    /**
     * Checks whether an index exists or not.
     *
     * @param EntityManager $em
     * @param               $tableName
     * @param               $indexName
     *
     * @return bool
     */
    private function indexExists(EntityManager $em, $tableName, $indexName)
    {
        $rs = $em->createNativeQuery(
            sprintf('SHOW INDEX FROM %s WHERE Key_name="%s"', $tableName, $indexName),
            (new ResultSetMapping())->addScalarResult('Key_name', 'Key_name')
        )->getResult();

        return count($rs) >= 1;
    }

    /**
     * Updates user id fields for one table.
     *
     * @param EntityManager $em
     * @param               $tableName
     * @param               $fields
     */
    private function doUpdateFields(EntityManager $em, $tableName, $fields)
    {
        foreach ($fields as $field) {
            $this->doUpdateField($em, $tableName, $field);
        }
    }

    /**
     * Updates user id field for one table.
     *
     * @param EntityManager $em
     * @param               $tableName
     * @param               $field
     *
     * @throws RuntimeException if old user id could not be converted to a new one.
     */
    private function doUpdateField(EntityManager $em, $tableName, $field)
    {
        $error = false;
        $fieldValues = [];
        $primaryFields = is_array($field['primary']) ? $field['primary'] : [$field['primary']];
        $selectFields = array_unique(array_merge($primaryFields, [$field['to_update']]));
        $rsm = new ResultSetMapping();

        foreach ($selectFields as $fieldName) {
            $rsm->addScalarResult($fieldName, $fieldName);
        }

        $results = $em->createNativeQuery(
            sprintf('SELECT %s FROM %s', implode(', ', $selectFields), $tableName),
            $rsm
        )->getResult();

        foreach ($results as $result) {
            if (($id = (int) $result[$field['to_update']]) < 1) {
                continue;
            }

            $whereClauses = array_map(function ($fieldName) use ($result) {
                return $fieldName . '=' . $result[$fieldName];
            }, $primaryFields);

            try {
                $user = self::getUserFromOldId($em, $id);
            } catch (RuntimeException $e) {
                $error = true;
                $result[] = $tableName;
                $table = (new TableHelper())
                    ->setHeaders(array_merge($primaryFields, [$field['to_update'], 'TableName']))
                    ->addRow($result);
                continue;
            }
            $fieldValues[] = [
                'user-id' => $user->getId(),
                'where-clauses' => $whereClauses,
            ];
        }

        if ($error) {
            echo ("The '".$field['to_update']."' field value for the following lines with the corresponding id".
            " of the '".$tableName."' table point to users that no longer exist.\n"
            . $table->render(new ConsoleOutput()) . "\n");
        }

        foreach ($fieldValues as $fieldValue) {
            $em->getConnection()->executeQuery($sql = sprintf('UPDATE %s SET %s=%d WHERE %s',
                $tableName,
                $field['to_update'],
                $fieldValue['user-id'],
                implode(' AND ', $fieldValue['where-clauses'])
            ));
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
            if (false === $this->tableExists($em, $tableName)) {
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
                ->setHeaders(array('id', 'login', 'email'))
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
     * @param \PDO          $conn
     */
    private function migrateModels(EntityManager $em, \PDO $conn)
    {
        $em->getEventManager()->removeEventSubscriber(new TimestampableListener());
        $this->updateModels($em, $conn);
        $em->getEventManager()->addEventSubscriber(new TimestampableListener());
    }

    private function tableExists(EntityManager $em , $tableName)
    {
        if (null === $this->tablesStatus) {
            $this->tablesStatus = array_map(function($row) {
                return $row['Name'];
            }, $em->createNativeQuery(
                "SHOW TABLE STATUS",
                new ResultSetMapping()
            )->getResult());
        }

        return in_array($tableName, $this->tablesStatus);
    }

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
    private function updateUsers(EntityManager $em, $conn)
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

            if ($n % 100 === 0) {
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
    private function updateLastModels(EntityManager $em, $conn)
    {
        $sql = "SELECT lastModel, usr_id
                FROM usr
                WHERE lastModel > 0";

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $n = 0;

        foreach ($rows as $row) {
            $user = self::getUserFromOldId($em, $row['usr_id']);

            try {
                $lastModel = self::getUserFromOldId($em, $row['lastModel']);
            } catch (NoResultException $e) {
               continue;
            }

            if (false === $lastModel->isTemplate()) {
                continue;
            }

            $user->setLastModel($lastModel);
            $em->persist($user);

            $n++;

            if ($n % 100 === 0) {
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
    private function updateModels(EntityManager $em, $conn)
    {
        $sql = "SELECT model_of, usr_id
                FROM usr
                WHERE model_of > 0";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $n = 0;
        foreach ($rows as $row) {
            $template = self::getUserFromOldId($em, $row['usr_id']);
            try {
                $owner = self::getUserFromOldId($em, $row['model_of']);
                $template->setModelOf($owner);
                $em->persist($owner);
            } catch (NoResultException $e) {
                // remove template with no owner
                $em->remove($template);
            }

            $n++;

            if ($n % 100 === 0) {
                $em->flush();
                $em->clear();
            }
        }

        $em->flush();
        $em->clear();

        $this->updateLastModels($em, $conn);
    }
}
