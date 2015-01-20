<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
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
class patch_380alpha2a implements patchInterface
{
    /**
     *
     * @var string
     */
    private $release = '3.8.0-alpha.2';

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
        $config = $app['phraseanet.configuration']->getConfig();

        if (isset($config['main']['database-test'])) {
            $config['main']['database-test']['path'] = '/tmp/db.sqlite';
        }

        $app['phraseanet.configuration']->setConfig($config);
    }
}
