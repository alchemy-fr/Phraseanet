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
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;

class patch_386alpha4a implements patchInterface
{
    /** @var string */
    private $release = '3.8.6-alpha.4';

    /** @var array */
    private $concern = array(base::APPLICATION_BOX);

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
        $sql = 'SELECT usr_id FROM usr WHERE usr_login LIKE "(#deleted_%"';
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();

        $repo = $app['EM']->getRepository('Entities\UsrList');

        $users = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($users as $user) {
            foreach ($repo->findUserLists(new \User_Adapter($user['usr_id'], $app)) as $list) {
                $app['EM']->remove($list);
            }
            $app['EM']->flush();
        }

        $stmt->closeCursor();

        return true;
    }
}
