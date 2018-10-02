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

class Upgrade39Sessions implements PreSchemaUpgradeInterface
{
    /**
     * {@inheritdoc}
     */
    public function apply(EntityManager $em, \appbox $appbox, Configuration $conf)
    {
        if ($this->tableExists($em, 'Sessions')) {
            $this->dropTable($em, 'SessionModules');
            $this->dropTable($em, 'Sessions');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isApplyable(Application $app)
    {
        return $this->tableExists($app['orm.em'], 'Sessions');
    }

    /**
     * {@inheritdoc}
     */
    public function rollback(EntityManager $em, \appbox $appbox, Configuration $conf)
    {

    }

    /**
     * Checks whether the table exists or not.
     *
     * @param EntityManager $em
     * @param string $table
     *
     * @return boolean
     */
    private function tableExists(EntityManager $em, $table)
    {
        return (Boolean) $em->createNativeQuery(
            'SHOW TABLE STATUS WHERE Name="'.$table.'" COLLATE utf8_bin ', (new ResultSetMapping())->addScalarResult('Name', 'Name')
        )->getOneOrNullResult();
    }

    /**
     * @param EntityManager $em
     * @param $tableName
     */
    private function dropTable(EntityManager $em, $tableName)
    {
        $em->getConnection()->getSchemaManager()->dropTable($tableName);
    }
}
