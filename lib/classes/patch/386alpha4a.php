<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

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
    public function getDoctrineMigrations()
    {
        return array('20140219000003');
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
        $repo = $app['orm.em']->getRepository('Phraseanet:UsrList');
        foreach ($app['orm.em']->getRepository('Phraseanet:User')->findDeleted() as $user) {
            foreach ($repo->findUserLists($user) as $list) {
                $app['orm.em']->remove($list);
            }
            $app['orm.em']->flush();
        }

        return true;
    }
}
