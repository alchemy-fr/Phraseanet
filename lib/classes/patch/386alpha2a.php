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
use Alchemy\Phrasea\Utilities\CrossDomainParser;
use Alchemy\Phrasea\Exception\RuntimeException;

class patch_386alpha2a implements patchInterface
{
    /** @var string */
    private $release = '3.8.6-alpha.2';

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

    public function getDoctrineMigrations()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function apply(base $appbox, Application $app)
    {
        $config = $app['phraseanet.configuration']->getConfig();

        $parser = new CrossDomainParser();
        try {
            $crossDomainConfig = $parser->parse($app['root.path'].'/public/crossdomain.xml');
        } catch (RuntimeException $e) {
            $crossDomainConfig = array(
                'allow-access-from' => array(
                    array(
                        'domain' => '*.cooliris.com',
                        'secure' => 'false',
                    )
                )
            );
        }

        $config['crossdomain'] = $crossDomainConfig;

        $app['phraseanet.configuration']->setConfig($config);

        return true;
    }
}
