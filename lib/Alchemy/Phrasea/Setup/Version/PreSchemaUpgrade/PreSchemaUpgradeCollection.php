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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Collection of Doctrine schema pre-upgrades
 */
class PreSchemaUpgradeCollection
{
    /** @var PreSchemaUpgradeInterface[] */
    private $upgrades = [];

    public function __construct(array $upgrades)
    {
        $this->upgrades = $upgrades;
    }

    /**
     * Applies all applyable upgrades
     *
     * @param Application $app
     */
    public function apply(Application $app, InputInterface $input, OutputInterface $output)
    {
        $applied = [];

        foreach ($this->upgrades as $upgrade) {
            if ($upgrade->isApplyable($app)) {
                try {
                    $upgrade->apply(
                        $app['orm.em'],
                        $app['phraseanet.appbox'],
                        $app['doctrine-migration.configuration']
                    );
                    $applied[] = $upgrade;
                } catch (\Exception $e) {
                    $upgrade->rollback(
                        $app['orm.em'],
                        $app['phraseanet.appbox'],
                        $app['doctrine-migration.configuration']
                    );
                    foreach (array_reverse($applied) as $done) {
                        $done->rollback(
                            $app['orm.em'],
                            $app['phraseanet.appbox'],
                            $app['doctrine-migration.configuration']
                        );
                    }
                    throw $e;
                }
            }
        }
    }
}
