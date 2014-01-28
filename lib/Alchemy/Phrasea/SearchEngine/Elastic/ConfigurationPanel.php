<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\SearchEngine\AbstractConfigurationPanel;
use Symfony\Component\HttpFoundation\Request;
use Alchemy\Phrasea\Core\Configuration\ConfigurationInterface;

class ConfigurationPanel extends AbstractConfigurationPanel
{
    private $searchEngine;

    public function __construct(ElasticSearchEngine $engine, ConfigurationInterface $conf)
    {
        $this->searchEngine = $engine;
        $this->conf = $conf;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'elastic-search-engine';
    }

    /**
     * {@inheritdoc}
     */
    public function get(Application $app, Request $request)
    {
        return $app['twig']->render('admin/search-engine/elastic-search.html.twig', ['configuration' => $this->getConfiguration()]);
    }

    /**
     * {@inheritdoc}
     */
    public function post(Application $app, Request $request)
    {
        $configuration = $this->getConfiguration();

        $configuration['host'] = $request->request->get('host');
        $configuration['port'] = $request->request->get('port');

        $this->saveConfiguration($configuration);

        return $app->redirectPath('admin_searchengine_get');
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        return isset($this->conf['main']['search-engine']['options']) ? $this->conf['main']['search-engine']['options'] : [];
    }
}
