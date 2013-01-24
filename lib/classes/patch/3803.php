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
class patch_3803 implements patchInterface
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
     * @param base $appbox
     */
    public function apply(base $appbox, Application $app)
    {
        $searchEngine = $app['phraseanet.registry']->get('GV_sphinx') ? 'sphinxsearch' : 'phrasea';

        $confs = $app['phraseanet.configuration']->getConfigurations();

        foreach ($confs as $env => $conf) {
            if (in_array($env, array('environment', 'key'))) {
                continue;
            }
            if (!isset($conf['search-engine'])) {
                $confs[$env]['search-engine'] = $searchEngine;
            }
        }

        $app['phraseanet.configuration']->setConfigurations($confs);

        $services = $app['phraseanet.configuration']->getServices();

        if (!isset($services['SearchEngine'])) {
            $app['phraseanet.configuration']->resetServices('SearchEngine');
        }

        if (!$app['phraseanet.registry']->get('GV_sphinx')) {
            $phraseaConfiguration = $app['phraseanet.SE']->getConfigurationPanel()->getConfiguration();

            if ($app['phraseanet.registry']->get('GV_phrasea_sort')) {
                $phraseaConfiguration['default_sort'] = $app['phraseanet.registry']->get('GV_phrasea_sort');
            }

            $app['phraseanet.SE']->getConfigurationPanel()->saveConfiguration($phraseaConfiguration);
        } else {
            $sphinxConfiguration = $app['phraseanet.SE']->getConfigurationPanel()->getConfiguration();

            if ($app['phraseanet.registry']->get('GV_sphinx_rt_port')) {
                $sphinxConfiguration['rt_port'] = $app['phraseanet.registry']->get('GV_sphinx_rt_port');
            }
            if ($app['phraseanet.registry']->get('GV_sphinx_rt_host')) {
                $sphinxConfiguration['rt_host'] = $app['phraseanet.registry']->get('GV_sphinx_rt_host');
            }
            if ($app['phraseanet.registry']->get('GV_sphinx_port')) {
                $sphinxConfiguration['port'] = $app['phraseanet.registry']->get('GV_sphinx_port');
            }
            if ($app['phraseanet.registry']->get('GV_sphinx_host')) {
                $sphinxConfiguration['host'] = $app['phraseanet.registry']->get('GV_sphinx_host');
            }

            $app['phraseanet.SE']->getConfigurationPanel()->saveConfiguration($sphinxConfiguration);
        }

        return;
    }

}

