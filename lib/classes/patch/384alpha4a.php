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

class patch_384alpha4a implements patchInterface
{
    /** @var string */
    private $release = '3.8.4-alpha.4';

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
        $config = $app['phraseanet.configuration']->getConfig();

        $config['session'] = array(
            'idle' => 0,
            'lifetime' => 604800,
        );

        $app['phraseanet.configuration']->setConfig($config);

        return true;
    }
}
