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

use Pimple\ServiceProviderInterface;
use Doctrine\DBAL\Migrations\Configuration\YamlConfiguration;
use Pimple\Container;

class DoctrineMigrationServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['doctrine-migration.configuration'] = function ($app) {
            $configuration = new YamlConfiguration($app['orm.em']->getConnection());
            $configuration->load(__DIR__.'/../../../../conf.d/migrations.yml');
            $configuration->setMigrationsDirectory(__DIR__.'/../../../../Alchemy/Phrasea/Setup/DoctrineMigration');

            return $configuration;
        };
    }
}
