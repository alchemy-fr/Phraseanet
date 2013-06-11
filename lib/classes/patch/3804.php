<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

class patch_3804 implements patchInterface
{
    /**
     *
     * @var string
     */
    private $release = '3.8.0.a3';

    /**
     *
     * @var Array
     */
    private $concern = array(base::APPLICATION_BOX);

    /**
     *
     * @return string
     */
    public function get_release()
    {
        return $this->release;
    }

    public function require_all_upgrades()
    {
        return false;
    }

    /**
     *
     * @return Array
     */
    public function concern()
    {
        return $this->concern;
    }

    /**
     * @param base        $appbox
     * @param Application $app
     */
    public function apply(base $appbox, Application $app)
    {
        $app['phraseanet.configuration']->setDefault('main', 'task-manager');

        return true;
    }
}
