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

use Doctrine\ORM\EntityManager;
use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Setup\DoctrineMigrations\MigrationRunner;

/**
 * Interface for DB schema upgrade that have to be done before Doctrine schema
 * upgrade
 */
interface PreSchemaUpgradeInterface
{
    /**
     * Applies the pre-upgrade.
     *
     * @param EntityManager $em
     * @param \appbox       $appbox
     * @param MigrationRunner $migrations
     *
     * @return mixed
     */
    public function apply(EntityManager $em, \appbox $appbox, MigrationRunner $migrations);

    /**
     * Rollback migration to origin state.
     *
     * @param EntityManager $em
     * @param MigrationRunner $migrations
     */
    public function rollback(EntityManager $em, \appbox $appbox, MigrationRunner $migrations);

    /**
     * Returns true if the Upgrade is applyable.
     *
     * @param Application $app
     *
     * @return Boolean
     */
    public function isApplyable(Application $app);
}
