<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Phrasea;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\SearchEngine\AbstractConfigurationPanel;
use Symfony\Component\HttpFoundation\Request;

class ConfigurationPanel extends AbstractConfigurationPanel
{
    protected $charsets;
    protected $searchEngine;

    public function __construct(PhraseaEngine $engine, PropertyAccess $conf)
    {
        $this->searchEngine = $engine;
        $this->conf = $conf;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'phrasea-engine';
    }

    /**
     * {@inheritdoc}
     */
    public function get(Application $app, Request $request)
    {
        $configuration = $this->getConfiguration();

        $params = [
            'configuration' => $configuration,
            'available_sort'=> $this->searchEngine->getAvailableSort(),
        ];

        return $app['twig']->render('admin/search-engine/phrasea.html.twig', $params);
    }

    /**
     * {@inheritdoc}
     */
    public function post(Application $app, Request $request)
    {
        $configuration = $this->getConfiguration();
        $configuration['date_fields'] = [];

        foreach ($request->request->get('date_fields', []) as $field) {
            $configuration['date_fields'][] = $field;
        }

        $configuration['default_sort'] = $request->request->get('default_sort');
        $configuration['stemming_enabled'] = (int) (Boolean) $request->request->get('stemming_enabled');

        $this->saveConfiguration($configuration);

        return $app->redirectPath('admin_searchengine_get');
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        $configuration = $this->conf->get(['main', 'search-engine', 'options'], []);

        if (!is_array($configuration)) {
            $configuration = [];
        }

        if (!isset($configuration['date_fields'])) {
            $configuration['date_fields'] = [];
        }

        if (!isset($configuration['default_sort'])) {
            $configuration['default_sort'] = null;
        }

        if (!isset($configuration['stemming_enabled'])) {
            $configuration['stemming_enabled'] = false;
        }

        return $configuration;
    }
}
