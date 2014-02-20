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
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;

class Upgrade39Feeds implements PreSchemaUpgradeInterface
{
    /**
     * {@inheritdoc}
     */
    public function apply(EntityManager $em, \appbox $appbox, Configuration $conf)
    {
        $this->doBackupFeedsTable($em);
    }

    /**
     * {@inheritdoc}
     */
    public function isApplyable(Application $app)
    {
        return $this->tableExists($app['EM'], 'feeds');
    }

    /**
     * {@inheritdoc}
     */
    public function rollback(EntityManager $em, \appbox $appbox, Configuration $conf)
    {
        if ($this->tableExists($em, 'feeds_backup')) {
            $em->getConnection()->executeQuery('RENAME TABLE `feeds_backup` TO `feeds`');
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
            'SHOW TABLE STATUS WHERE Name="'.$table.'"', (new ResultSetMapping())->addScalarResult('Name', 'Name')
        )->getOneOrNullResult();
    }

    /**
     * Renames feed table.
     *
     * @param EntityManager $em
     */
    private function doBackupFeedsTable(EntityManager $em)
    {
        $em->getConnection()->executeQuery('RENAME TABLE `feeds` TO `feeds_backup`');
    }
}
