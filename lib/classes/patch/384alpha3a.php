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

class patch_384alpha3a implements patchInterface
{
    /** @var string */
    private $release = '3.8.4-alpha.3';

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
    public function getDoctrineMigrations()
    {
        return [];
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

        $config['api_cors'] = [
            'enabled' => false,
            'allow_credentials' => false,
            'allow_origin'    => [],
            'allow_headers' => [],
            'allow_methods' => [],
            'expose_headers' => [],
            'max_age' => 0,
            'hosts' => [],
        ];

        $app['phraseanet.configuration']->setConfig($config);

        return true;
    }
}
