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

class patch_384alpha1a implements patchInterface
{
    /** @var string */
    private $release = '3.8.4-alpha.1';

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
        $config = $app['phraseanet.configuration']->getConfig();

        $config['h264-pseudo-streaming'] = array(
            'enabled' => false,
            'type'    => null,
            'mapping' => array(),
        );

        $app['phraseanet.configuration']->setConfig($config);

        return true;
    }
}
