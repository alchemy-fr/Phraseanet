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

class patch_390alpha15a extends patchAbstract
{
    /** @var string */
    private $release = '3.9.0-alpha.15';

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
        return ['token'];
    }

    /**
     * {@inheritdoc}
     */
    public function apply(\appbox $appbox, Application $app)
    {
        if (!$this->tableExists($app['EM'], 'tokens_backup')) {
            return true;
        }

        $sql = 'INSERT INTO Tokens
                    (value, user_id, type, data, created, updated, expiration)
                    (SELECT value, usr_id, type, datas, created_on, created_on, expire_on FROM tokens_backup)';
        $appbox->get_connection()->exec($sql);

        return true;
    }
}
