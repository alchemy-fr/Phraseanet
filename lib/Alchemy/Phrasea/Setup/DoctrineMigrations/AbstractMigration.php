<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Setup\DoctrineMigrations;

use Alchemy\Phrasea\Exception\RuntimeException;
use Doctrine\DBAL\Migrations\AbstractMigration as BaseMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManager;

abstract class AbstractMigration extends BaseMigration
{
    /** @var EntityManager */
    private $em;

    /**
     * Sets EntityManager.
     *
     * @param EntityManager $em
     *
     * @return AbstractMigration
     */
    public function setEntityManager(EntityManager $em)
    {
        $this->em = $em;

        return $this;
    }

    /**
     * Gets EntityManager.
     *
     * @return EntityManager
     *
     * @throws RuntimeException
     */
    public function getEntityManager()
    {
        if (null === $this->em) {
            throw new RuntimeException('EntityManager must be injected.');
        }

        return $this->em;
    }

    /**
     * @inheritdoc
     */
    final public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->doUpSql($schema);

        return $this;
    }

    /**
     * @inheritdoc
     */
    final public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");

        $this->doDownSql($schema);

        return $this;
    }

    /**
     * Executes update SQL.
     */
    abstract public function doUpSql(Schema $schema);

    /**
     * Execute downgrade SQL.
     */
    abstract public function doDownSql(Schema $schema);

    /**
     * Tells whether the patch can be executed or not.
     *
     * @returns Boolean
     */
    public function isAlreadyApplied()
    {
        return false;
    }

    /**
     * Tells whether the table exists or not.
     *
     * @param $tableName
     *
     * @returns Boolean
     */
    protected function tableExists($tableName)
    {
        $rs = $this->getEntityManager()->getConnection()->fetchAll('SHOW TABLE STATUS');

        foreach ($rs as $row) {
            if ($tableName === $row['Name']) {
                return true;
            }
        }

        return false;
    }
}
