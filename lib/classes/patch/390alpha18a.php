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

class patch_390alpha18a extends patchAbstract
{
    /** @var string */
    private $release = '3.9.0-alpha.18';

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
        return ['20131118000009', '20131118000003', '20131118000001', '20131118000006'];
    }

    /**
     * {@inheritdoc}
     */
    public function apply(base $appbox, Application $app)
    {
        $app['orm.em']->getConnection()->executeUpdate('
            DELETE lf FROM LazaretFiles lf
            INNER JOIN LazaretSessions ls ON (ls.id = lf.lazaret_session_id)
            LEFT JOIN Users u ON (ls.user_id = u.id)
            WHERE u.id IS NULL'
        );

        $app['orm.em']->getConnection()->executeUpdate('
          DELETE ls FROM LazaretSessions AS ls
            LEFT JOIN Users u ON (ls.user_id = u.id)
            WHERE u.id IS NULL'
        );

        $app['orm.em']->getConnection()->executeUpdate('
            DELETE fi FROM FeedItems AS fi
            INNER JOIN FeedEntries fe ON (fe.id = fi.entry_id)
            LEFT JOIN Users u ON (fe.publisher_id = u.id)
            WHERE u.id IS NULL'
        );

        $app['orm.em']->getConnection()->executeUpdate('
            DELETE fe FROM FeedEntries AS fe
            LEFT JOIN Users u ON (fe.publisher_id = u.id)
            WHERE u.id IS NULL'
        );

        $app['orm.em']->getConnection()->executeUpdate(
            'DELETE se FROM Sessions AS se
             LEFT JOIN Users u ON (se.user_id = u.id)
             WHERE u.id IS NULL'
        );

        return true;
    }
}
