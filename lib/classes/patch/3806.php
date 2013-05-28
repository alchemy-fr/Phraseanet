<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Symfony\Component\Yaml\Yaml;

class patch_3806 implements patchInterface
{
    /** @var string */
    private $release = '3.8.0.a6';

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
        return true;
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
        $parser = new Yaml();
        $data = $parser->parse(file_get_contents(__DIR__ . '/../../conf.d/config.yml'));

        $fields = $data['prod']['registration-fields'];
        $authentication = $data['prod']['authentication'];

        $confs = $app['phraseanet.configuration']->getConfigurations();

        foreach ($confs as $env => $conf) {

            if (in_array($env, array('environment', 'key'))) {
                continue;
            }

            if (!isset($conf['registration-fields'])) {
                $confs[$env]['registration-fields'] = $fields;
            }

            if (!isset($conf['authentication'])) {
                $confs[$env]['authentication'] = $authentication;
            }
        }

        $app['phraseanet.configuration']->setConfigurations($confs);
    }
}