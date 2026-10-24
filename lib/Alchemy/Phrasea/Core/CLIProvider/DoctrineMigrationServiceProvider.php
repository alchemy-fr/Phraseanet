<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\CLIProvider;

use Alchemy\Phrasea\Setup\DoctrineMigrations\MigrationRunner;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class DoctrineMigrationServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['doctrine-migration.runner'] = function ($app) {
            return MigrationRunner::fromEntityManager($app['orm.em']);
        };

        $app['doctrine-migration.dependency-factory'] = function ($app) {
            return $app['doctrine-migration.runner']->getDependencyFactory();
        };

        $app['doctrine-migration.configuration'] = function ($app) {
            return $app['doctrine-migration.dependency-factory']->getConfiguration();
        };
    }
}
