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

class patch_380alpha2a extends patchAbstract
{
    /** @var string */
    private $release = '3.8.0-alpha.2';

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
        if ($app['conf']->has(['main', 'database-test'])) {
            $app['conf']->set(['main', 'database-test', 'path'], '/tmp/db.sqlite');
        }
   }
}
