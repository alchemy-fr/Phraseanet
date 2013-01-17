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
        $searchEngine = $app['phraseanet.registry']->get('GV_sphinx') ? 'sphinx-search' : 'phrasea';

        $app['phraseanet.registry']->set('GV_search_engine', $searchEngine, \registry::TYPE_ENUM);

        $phraseaConfiguration = null;
        $phraseaConfigFile = __DIR__ . '/../../../config/phrasea-engine.json';

        if (file_exists($phraseaConfigFile)) {
            $phraseaConfiguration = json_decode(file_get_contents($phraseaConfigFile), true);
        }
        if (!is_array($phraseaConfiguration)) {
            $phraseaConfiguration = array();
        }

        if ($app['phraseanet.registry']->get('GV_phrasea_sort')) {
            $phraseaConfiguration['default_sort'] = $app['phraseanet.registry']->get('GV_phrasea_sort');
        }
        
        file_put_contents($phraseaConfigFile, $phraseaConfiguration);
        
        $sphinxConfiguration = null;
        $sphinxConfigFile = __DIR__ . '/../../../config/sphinx-search.json';

        if (file_exists($sphinxConfigFile)) {
            $sphinxConfiguration = json_decode(file_get_contents($sphinxConfigFile), true);
        }
        if (!is_array($sphinxConfiguration)) {
            $sphinxConfiguration = array();
        }

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
        
        file_put_contents($sphinxConfigFile, $sphinxConfiguration);

        return;
    }

}

