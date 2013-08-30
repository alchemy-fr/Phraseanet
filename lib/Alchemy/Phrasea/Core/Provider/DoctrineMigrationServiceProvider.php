<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Migration;
use Doctrine\DBAL\Migrations\OutputWriter;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class DoctrineMigrationServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['doctrine.migration'] = $app->share(function($app) {
            $output = new ConsoleOutput();
            $configuration = new Configuration(
                $app['EM']->getConnection(),
                new OutputWriter(function($message) use ($output) {
                    return $output->writeln($message);
                })
            );

            $configuration->setName('Phraseanet Doctrine Migrations');
            $configuration->setMigrationsTableName('doctrine_migrations');
            $configuration->setMigrationsNamespace('Alchemy\Phrasea\Migrations');
            $configuration->setMigrationsDirectory(__DIR__ . '/../../Migrations');
            $configuration->registerMigrationsFromDirectory(__DIR__ . '/../../Migrations');

            return new Migration($configuration);
        });
    }

    public function boot(Application $app)
    {
    }
}
