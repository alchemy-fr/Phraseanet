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

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class patch_380 implements patchInterface
{
    /**
     *
     * @var string
     */
    private $release = '3.8.0.a2';

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
     * @param base        $databox
     * @param Application $app
     */
    public function apply(base $databox, Application $app)
    {
        $connexions = $app['phraseanet.configuration']->getConnexions();

        if (isset($connexions['test_connexion'])) {
            $connexions['test_connexion']['path'] = '/tmp/db.sqlite';
        }

        $app['phraseanet.configuration']->setConnexions($connexions);
    }
}
