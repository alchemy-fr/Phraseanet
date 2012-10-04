<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Setup\Version\Migration;

use Alchemy\Phrasea\Application;

class Migration35 implements MigrationInterface
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function migrate()
    {
        if (!file_exists(__DIR__ . '/../../../../../../config/connexion.inc')
            || !file_exists(__DIR__ . '/../../../../../../config/config.inc')) {
            throw new \LogicException('Required config files not found');
        }

        $this->app['phraseanet.configuration']->initialize();

        $retrieve_old_credentials = function() {
            require __DIR__ . '/../../../../../../config/connexion.inc';

            return array(
                'hostname' => $hostname,
                'port'     => $port,
                'user'     => $user,
                'password' => $password,
                'dbname'   => $dbname,
            );
        };

        $connexions = $this->app['phraseanet.configuration']->getConnexions();

        foreach ($retrieve_old_credentials() as $key => $value) {
            $key = $key == 'hostname' ? 'host' : $key;
            $connexions['main_connexion'][$key] = (string) $value;
        }

        $this->app['phraseanet.configuration']->setConnexions($connexions);

        $configs = $this->app['phraseanet.configuration']->getConfigurations();

        $retrieve_old_parameters = function() {
            require __DIR__ . '/../../../../../../config/config.inc';

            return array(
                'servername' => $servername
            );
        };

        $old_parameters = $retrieve_old_parameters();

        foreach ($configs as $env => $conf) {
            if ( ! is_array($configs[$env]) || ! array_key_exists('phraseanet', $configs[$env])) {
                continue;
            }

            $configs[$env]['phraseanet']['servername'] = $old_parameters['servername'];
        }

        rename(__DIR__ . '/../../../../../../config/connexion.inc', __DIR__ . '/../../../../../../config/connexion.inc.old');
        rename(__DIR__ . '/../../../../../../config/config.inc', __DIR__ . '/../../../../../../config/config.inc.old');


        $this->app['phraseanet.configuration']->setConfigurations($configs);
        $this->app['phraseanet.configuration']->setEnvironnement('prod');
    }
}
