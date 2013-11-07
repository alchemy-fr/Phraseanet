<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\CLIProvider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Doctrine\DBAL\Migrations\Configuration\YamlConfiguration;

class DoctrineMigrationServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['doctrine-migration.configuration'] = $app->share(function ($app) {
            $configuration = new YamlConfiguration($app['EM']->getConnection());
            $configuration->load(sprintf('%s/lib/conf.d/migrations.yml', $app['root.path']));
            $configuration->setMigrationsDirectory(sprintf('%s/lib/Alchemy/Phrasea/Setup/DoctrineMigration', $app['root.path']));

            return $configuration;
        });
    }

    public function boot(Application $app)
    {
    }
}
