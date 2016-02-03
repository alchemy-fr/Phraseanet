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
use Alchemy\Phrasea\Model\Entities\FtpCredential;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;

class Upgrade39Users implements PreSchemaUpgradeInterface
{
   /**
     * {@inheritdoc}
     */
    public function apply(EntityManager $em, \appbox $appbox, Configuration $conf)
    {
        // Sanitize usr table
        $this->sanitizeUsrTable($em);
        // Creates User schema
        $version = $conf->getVersion('20131118000009');
        if (false === $version->isMigrated()) {
            $version->execute('up');
        }
        $version = $conf->getVersion('20131118000007');
        if (false === $version->isMigrated()) {
            $version->execute('up');
        }
        $this->alterTablesUp($em);

        try {
            $em->getConnection()->beginTransaction();
            $em->getConnection()->query('SET FOREIGN_KEY_CHECKS=0');
            // Creates user entities
            $this->updateUsers($em);
            $this->updateFtpSettings($em);
            // Creates user model references
            $this->updateTemplateOwner($em);
            $this->updateLastAppliedModels($em);
            $this->cleanForeignKeyReferences($em);
            $em->getConnection()->query('SET FOREIGN_KEY_CHECKS=1');
            $em->getConnection()->commit();
            $this->renameTable($em, 'up');
        } catch (\Exception $e) {
            $em->getConnection()->rollback();
            $em->close();
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isApplyable(Application $app)
    {
        return false === $this->tableExists($app['orm.em'], 'Users');
    }

    /**
     * {@inheritdoc}
     */
    public function rollback(EntityManager $em, \appbox $appbox, Configuration $conf)
    {
        // truncate created tables
        $this->emptyTables($em);
        // rollback schema
        $this->alterTablesDown($em);
        $version = $conf->getVersion('20131118000007');
        if ($version->isMigrated()) {
            $version->execute('down');
        }
        $version = $conf->getVersion('20131118000009');
        if ($version->isMigrated()) {
            $version->execute('down');
        }
    }

    private function renameTable(EntityManager $em, $direction)
    {
        switch ($direction) {
            case 'up':
                $sql = 'RENAME TABLE usr TO usr_backup';
                break;
            case 'down':
                $sql = 'RENAME TABLE usr_backup TO usr';
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Direction %s is not recognized.', $direction));
        }
        $em->getConnection()->executeUpdate($sql);
    }

    private function alterTablesUp(EntityManager $em)
    {
        foreach ([
            'UsrListOwners'          => "ALTER TABLE UsrListOwners CHANGE usr_id user_id INT DEFAULT NULL",
            'Sessions'               => "ALTER TABLE Sessions CHANGE usr_id user_id INT DEFAULT NULL",
            'Baskets'                => "ALTER TABLE Baskets CHANGE usr_id user_id INT DEFAULT NULL",
            'StoryWZ'                => "ALTER TABLE StoryWZ CHANGE usr_id user_id INT DEFAULT NULL",
            'LazaretSessions'        => "ALTER TABLE LazaretSessions CHANGE usr_id user_id INT DEFAULT NULL",
            'ValidationParticipants' => "ALTER TABLE ValidationParticipants CHANGE usr_id user_id INT DEFAULT NULL",
            'UsrAuthProviders'       => "ALTER TABLE UsrAuthProviders CHANGE usr_id user_id INT DEFAULT NULL",
            'UsrListsContent'        => "ALTER TABLE UsrListsContent CHANGE usr_id user_id INT DEFAULT NULL",
        ] as $table => $sql) {
            if ($this->tableExists($em, $table)) {
                $em->getConnection()->executeUpdate($sql);
            }
        }
    }

    private function alterTablesDown(EntityManager $em)
    {
        foreach ([
            'Baskets'                => "ALTER TABLE Baskets CHANGE user_id usr_id INT DEFAULT NULL",
            'LazaretSessions'        => "ALTER TABLE LazaretSessions CHANGE user_id usr_id INT DEFAULT NULL",
            'Sessions'               => "ALTER TABLE Sessions CHANGE user_id usr_id INT DEFAULT NULL",
            'StoryWZ'                => "ALTER TABLE StoryWZ CHANGE user_id usr_id INT DEFAULT NULL",
            'UsrAuthProviders'       => "ALTER TABLE UsrAuthProviders CHANGE user_id usr_id INT DEFAULT NULL",
            'UsrListOwners'          => "ALTER TABLE UsrListOwners CHANGE user_id usr_id INT DEFAULT NULL",
            'UsrListsContent'        => "ALTER TABLE UsrListsContent CHANGE user_id usr_id INT DEFAULT NULL",
            'ValidationParticipants' => "ALTER TABLE ValidationParticipants CHANGE user_id usr_id INT DEFAULT NULL",
        ] as $table => $sql) {
            if ($this->tableExists($em, $table)) {
                $em->getConnection()->executeUpdate($sql);
            }
        }
    }

    /**
     * Checks whether the table exists or not.
     *
     * @param $tableName
     *
     * @return boolean
     */
    private function tableExists(EntityManager $em, $table)
    {
        return (Boolean) $em->createNativeQuery(
            'SHOW TABLE STATUS WHERE Name = :table', (new ResultSetMapping())->addScalarResult('Name', 'Name')
        )->setParameter(':table', $table)->getOneOrNullResult();
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
            $em->getConnection()->executeUpdate(sprintf('UPDATE usr SET usr_login="%s" WHERE usr_id=%d', $row['login_utf8'], $row['usr_id']));
        }

        foreach ([
            // drop index
            "ALTER TABLE usr DROP INDEX usr_login;",
            // change field type
            "ALTER TABLE usr MODIFY usr_login VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_bin;",
            // recreate index
            "CREATE UNIQUE INDEX usr_login ON usr (usr_login);"
        ] as $sql) {
            $em->getConnection()->executeUpdate($sql);
        }
    }

    private function cleanForeignKeyReferences(EntityManager $em)
    {
        $schemas = [
            "Baskets"=> [
                "referenced_by" => [
                    "Orders" => "basket_id",
                    "BasketElements" => "basket_id",
                    "ValidationSessions" => "basket_id",
                ],
                'field' => ['user_id', 'pusher_id'],
            ],
            "LazaretSessions" => [
                "referenced_by" => [
                    "LazaretFiles" => "lazaret_session_id"
                ],
                'field' => ['user_id'],
            ],
            "Sessions" => [
                "referenced_by" => [
                    "SessionModules" => "session_id"
                ],
                'field' => ['user_id'],
            ],
            "StoryWZ" => [
                "referenced_by" => [],
                'field' => ['user_id'],
            ],
            "UsrAuthProviders" => [
                "referenced_by" => [],
                'field' => ['user_id'],
            ],
            "UsrListsContent" => [
                "referenced_by" => [],
                'field' => ['user_id'],
            ],
            "UsrListOwners" => [
                "referenced_by" => [],
                'field' => ['user_id'],
            ],
            "ValidationParticipants" => [
                "referenced_by" => [
                    "ValidationDatas" => "participant_id"
                ],
                'field' => ['user_id'],
            ],
            "ValidationSessions" => [
                "referenced_by" => [
                    "ValidationParticipants" => "validation_session_id"
                ],
                'field' => ['initiator_id'],
            ],
        ];

        foreach ($schemas as $tableName => $data) {
            foreach ($data['field'] as $field) {
                $this->deleteForeignKey($em, $schemas, $field, $tableName);
            }
        }
    }

    private function deleteForeignKey(EntityManager $em, $schemas, $field, $tableName, $wrongIds = ' NOT IN (SELECT id FROM Users WHERE deleted = 0 )')
    {
        if (false === $this->tableExists($em, $tableName)) {
            return;
        }

        $sql = sprintf('SELECT id FROM %s WHERE %s %s', $tableName, $field, $wrongIds);
        $rs = $em->getConnection()->executeQuery($sql)->fetchAll(\PDO::FETCH_COLUMN);

        if (count($rs) === 0) {
            return;
        }

        if (isset($schemas[$tableName])) {
            array_walk($schemas[$tableName]['referenced_by'], function ($field, $tableName) use ($em, $schemas, $rs) {
                $this->deleteForeignKey($em, $schemas, $field, $tableName, ' IN ('.implode(', ', $rs).')');
            });
        }

        array_walk($rs, function ($value) use ($em, $schemas, $tableName, $field) {
            $sql = sprintf('DELETE FROM %s WHERE id = :value', $tableName);
            $em->getConnection()->executeUpdate($sql, [':value' => $value]);
        });
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

        if ($connection->getSchemaManager()->tablesExist([ $meta->getTableName() ])) {
            $connection->beginTransaction();

            try {
                $connection->query('SET FOREIGN_KEY_CHECKS=0');
                $connection->executeUpdate($dbPlatform->getTruncateTableSql($meta->getTableName()));
                $connection->query('SET FOREIGN_KEY_CHECKS=1');
                $connection->commit();
            } catch (\Exception $e) {
                $connection->rollback();
                throw $e;
            }
        }
    }

    /**
     * Check whether the usr table has a nonce column or not.
     *
     * @param EntityManager $em
     *
     * @return boolean
     */
    private function hasField(EntityManager $em, $fieldName)
    {
        return (Boolean) $em->createNativeQuery(
            "SHOW FIELDS FROM usr WHERE Field = :field",
            (new ResultSetMapping())->addScalarResult('Field', 'Field')
        )->setParameter(':field', $fieldName)->getOneOrNullResult();
    }

    /**
     * Sets user entity from usr table.
     */
    private function updateUsers(EntityManager $em)
    {
        $em->getConnection()->executeUpdate(
            'INSERT INTO Users
            (
                id,                     activity,               address,                admin,
                can_change_ftp_profil,  can_change_profil,      city,                   company,
                country,                email,                  fax,                    first_name,
                geoname_id,             guest,                  job,                    last_connection,
                last_name,              ldap_created,           locale,                 login,
                mail_locked,            last_model,             mail_notifications,     nonce,
                password,               push_list,              request_notifications,  salted_password,
                gender,                 phone,                  timezone,               zip_code,
                created,                updated,                deleted
            )
            (
                SELECT
                usr_id,                 activite,               adresse,                create_db,
                canchgftpprofil,        canchgprofil,           ville,                  societe,
                pays,                   usr_mail,               fax,                    usr_prenom,
                geonameid,              invite,                 fonction,               last_conn,
                usr_nom,                ldap_created,           locale,                 usr_login,
                mail_locked,            NULL AS lastModel,      mail_notifications,     '.($this->hasField($em, 'nonce') ? 'nonce' : 'NULL AS nonce').',
                usr_password,           push_list,              request_notifications,  '.($this->hasField($em, 'salted_password') ? 'salted_password' : '0 AS salted_password').',
                usr_sexe,               tel,                    timezone,               cpostal,
                usr_creationdate,       usr_modificationdate,   0
                FROM usr
            )'
        );

        $em->getConnection()->executeUpdate('UPDATE Users SET geoname_id=NULL WHERE geoname_id=0');
        $em->getConnection()->executeUpdate(
            'UPDATE Users SET locale=NULL WHERE locale NOT IN (:locales)',
            ['locales' => array_keys(Application::getAvailableLanguages())],
            ['locales' => Connection::PARAM_STR_ARRAY]
        );
        $em->getConnection()->executeUpdate('UPDATE Users SET deleted=1, login=SUBSTRING(login, 11) WHERE login LIKE "(#deleted_%"');
    }

    private function updateFtpSettings(EntityManager $em)
    {
        $offset = 0;
        $perBatch = 100;

        do {
            $builder = $em->getConnection()->createQueryBuilder();
            $sql = $builder
                ->select(
                    'u.usr_id',
                    'u.activeFTP',
                    'u.addrFTP',
                    'u.loginFTP',
                    'u.retryFTP',
                    'u.passifFTP',
                    'u.pwdFTP',
                    'u.destFTP',
                    'u.prefixFTPfolder'
                )
                ->from('usr', 'u')
                ->where(
                    $builder->expr()->notLike('u.usr_login', $builder->expr()->literal('(#deleted_%')),
                    $builder->expr()->eq('u.model_of', 0),
                    $builder->expr()->neq('u.addrFTP', $builder->expr()->literal(''))
                )
                ->setFirstResult($offset)
                ->setMaxResults($perBatch)
                ->getSQL();

            $rs = $em->getConnection()->fetchAll($sql);

            foreach ($rs as $row) {
                try {
                    $user = $em->createQuery('SELECT PARTIAL u.{id} FROM Phraseanet:User u WHERE u.id = :id')
                        ->setParameters(['id' => $row['usr_id']])
                        ->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
                        ->getSingleResult();
                } catch (NoResultException $e) {
                    continue;
                }

                $credential = new FtpCredential();
                $credential->setActive($row['activeFTP']);
                $credential->setAddress($row['addrFTP']);
                $credential->setLogin($row['loginFTP']);
                $credential->setMaxRetry((Integer) $row['retryFTP']);
                $credential->setPassive($row['passifFTP']);
                $credential->setPassword($row['pwdFTP']);
                $credential->setReceptionFolder($row['destFTP']);
                $credential->setRepositoryPrefixName($row['prefixFTPfolder']);
                $credential->setUser($user);

                $em->persist($credential);
            }

            $em->flush();
            $em->clear();

            $offset += $perBatch;
        } while (count($rs) > 0);

        return true;
    }

    /**
     * Sets last_model from usr table.
     */
    private function updateLastAppliedModels(EntityManager $em)
    {
        $em->getConnection()->executeUpdate('
            UPDATE Users
                INNER JOIN usr ON (
                    usr.usr_id = Users.id
                    AND Users.deleted=0
                    AND usr.lastModel IS NOT NULL
                    AND usr.lastModel != ""
                )
                LEFT JOIN Users TableTemp ON (usr.lastModel = TableTemp.login)
                SET Users.last_model = TableTemp.id
        ');
    }

    /**
     * Sets model from usr table.
     */
    private function updateTemplateOwner(EntityManager $em)
    {

        $em->getConnection()->executeUpdate('
            UPDATE Users
                INNER JOIN usr ON (
                    usr.usr_id = Users.id
                    AND usr.model_of IS NOT NULL
                    AND usr.model_of>0
                )
                SET Users.model_of = usr.model_of
        ');

        $em->getConnection()->executeUpdate('
            DELETE from Users
            WHERE id IN (
                SELECT id FROM (
                    SELECT templates.id
                    FROM Users users, Users templates
                    WHERE templates.model_of = users.id AND (users.deleted = 1 OR users.model_of IS NOT NULL)
                ) as temporaryTable
            )
        ');

        $em->getConnection()->executeUpdate('
            DELETE from Users
            WHERE id IN (
                SELECT id FROM (
                    SELECT templates.id
                    FROM Users templates
                    WHERE templates.model_of NOT IN (SELECT id FROM Users)
                ) as temporaryTable
            )
        ');

        $em->getConnection()->executeUpdate(' DELETE from Users WHERE deleted = 1 AND model_of IS NOT NULL');
    }
}
