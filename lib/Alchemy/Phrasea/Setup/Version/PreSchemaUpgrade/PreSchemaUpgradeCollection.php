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

/**
 * Collection of Doctrine schema pre-upgrades
 */
class PreSchemaUpgradeCollection
{
    /** @var PreSchemaUpgradeInterface[] */
    private $upgrades = array();

    public function __construct()
    {
        $this->upgrades[] = new Upgrade39();
    }

    /**
     * Applies all applyable upgrades
     *
     * @param Application $app
     */
    public function apply(Application $app)
    {
        foreach ($this->upgrades as $upgrade) {
            if ($upgrade->isApplyable($app)) {
                $upgrade->apply(
                    $app['EM'],
                    $app['phraseanet.appbox'],
                    $app['doctrine-migration.configuration']
                );
            }
        }
    }
}
