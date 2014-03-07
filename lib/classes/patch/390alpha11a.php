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
use Alchemy\Phrasea\Model\Entities\Task;

class patch_390alpha11a extends patchAbstract
{
    /** @var string */
    private $release = '3.9.0-alpha.11';

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
        $app['conf']->set(['main', 'task-manager', 'status'], 'started');
        $app['conf']->set(['main', 'websocket-server'], [
            'host' => parse_url($app['conf']->get('servername'), PHP_URL_HOST),
            'port' => 9090,
            'ip'   => '0.0.0.0',
        ]);
        $app['conf']->set(['main', 'task-manager', 'listener'], [
            'protocol' => 'tcp',
            'host'     => '127.0.0.1',
            'port'     => 6660,
            'linger'   => 500,
        ]);
        $app['conf']->set(['main', 'websocket-server', 'subscriber'], [
            'protocol' => 'tcp',
            'host'     => '127.0.0.1',
            'port'     => 13598,
        ]);
    }
}
