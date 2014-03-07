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
    public function getDoctrineMigrations()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function apply(\appbox $appbox, Application $app)
    {
        $app['conf']->remove(['main', 'api-timers']);

        if ($this->tableHasField($app['EM'], 'api_logs', 'api_log_ressource')) {
            $sql = 'UPDATE api_logs SET api_log_resource = api_log_ressource';
            $app['phraseanet.appbox']->get_connection()->executeUpdate($sql);
        }

        return true;
    }
}
