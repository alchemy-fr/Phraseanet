<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Setup\Version\Migration;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\Configuration;

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

        $config = $this->app['configuration.store']->initialize()->getConfig();

        foreach ($config['registration-fields'] as $key => $field) {
            $config['registration-fields'][$key]['required'] = (boolean) $field['required'];
        }

        $retrieve_old_credentials = function () {
            require __DIR__ . '/../../../../../../config/connexion.inc';

            return [
                'hostname' => $hostname,
                'port'     => $port,
                'user'     => $user,
                'password' => $password,
                'dbname'   => $dbname,
            ];
        };

        foreach ($retrieve_old_credentials() as $key => $value) {
            $key = $key == 'hostname' ? 'host' : $key;
            $config['main']['database'][$key] = (string) $value;
        }

        $retrieve_old_parameters = function () {
            require __DIR__ . '/../../../../../../config/config.inc';

            return [
                'servername' => $servername
            ];
        };

        $old_parameters = $retrieve_old_parameters();

        $config['main']['servername'] = $old_parameters['servername'];

        rename(__DIR__ . '/../../../../../../config/connexion.inc', __DIR__ . '/../../../../../../config/connexion.inc.old');
        rename(__DIR__ . '/../../../../../../config/config.inc', __DIR__ . '/../../../../../../config/config.inc.old');

        $this->app['configuration.store']->setConfig($config);
    }
}
