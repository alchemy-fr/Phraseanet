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
use Doctrine\ORM\EntityManager;

class Upgrade39 implements PreSchemaUpgradeInterface
{
    /**
     * {@inheritdoc}
     */
    public function apply(EntityManager $em)
    {
        $em->getConnection()->executeQuery('RENAME TABLE `feeds` TO `feeds_backup`');
    }

    /**
     * {@inheritdoc}
     */
    public function isApplyable(Application $app)
    {
        $rs = $app['phraseanet.appbox']->get_connection()->query('SHOW TABLE STATUS');
        $found = false;

        foreach ($rs as $row) {
            if ('feeds' === $row['Name']) {
                $found = true;
                break;
            }
        }

        return $found;
    }
}

