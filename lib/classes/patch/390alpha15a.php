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
    public function getDoctrineMigrations()
    {
        return ['20140305000001'];
    }

    /**
     * {@inheritdoc}
     */
    public function apply(base $appbox, Application $app)
    {
        if (!$this->tableExists($app['orm.em'], 'tokens_backup')) {
            return true;
        }

        $app['orm.em']->getConnection()->executeUpdate('
            INSERT INTO Tokens
            (
                `value`, user_id, `type`,   `data`,
                created, updated, expiration
            )
            (
                SELECT
                tb.`value`,     tb.usr_id,      tb.`type`,    tb.datas,
                tb.created_on,  tb.created_on,  tb.expire_on
                FROM tokens_backup tb
                INNER JOIN Users u ON (u.id = tb.usr_id)
            )');

        return true;
    }
}
