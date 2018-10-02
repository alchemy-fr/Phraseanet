<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

class patch_390alpha14a extends patchAbstract
{
    /** @var string */
    private $release = '3.9.0-alpha.14';

    /** @var array */
    private $concern = [base::APPLICATION_BOX];

    /**
     * {@inheritdoc}
     */
    public function get_release()
    {
        return $this->release;
    }

    /**
     * {@inheritdoc}
     */
    public function require_all_upgrades()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function concern()
    {
        return $this->concern;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(base $appbox, Application $app)
    {
        $app['conf']->remove(['main', 'api-timers']);

        if ($this->tableHasField($app['orm.em'], 'api_logs', 'api_log_ressource')) {
            $sql = "ALTER TABLE api_logs CHANGE api_log_ressource api_log_resource varchar(64)";
            $app->getApplicationBox()->get_connection()->executeUpdate($sql);
        }

        return true;
    }
}
