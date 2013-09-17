<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Phrasea;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\SearchEngine\AbstractConfigurationPanel;
use Symfony\Component\HttpFoundation\Request;
use Alchemy\Phrasea\Core\Configuration\ConfigurationInterface;

class ConfigurationPanel extends AbstractConfigurationPanel
{
    protected $charsets;
    protected $searchEngine;

    public function __construct(PhraseaEngine $engine, ConfigurationInterface $conf)
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

        $params = array(
            'configuration' => $configuration,
            'available_sort'=> $this->searchEngine->getAvailableSort(),
        );

        return $app['twig']->render('admin/search-engine/phrasea.html.twig', $params);
    }

    /**
     * {@inheritdoc}
     */
    public function post(Application $app, Request $request)
    {
        $configuration = $this->getConfiguration();
        $configuration['date_fields'] = array();

        foreach ($request->request->get('date_fields', array()) as $field) {
            $configuration['date_fields'][] = $field;
        }

        $configuration['default_sort'] = $request->request->get('default_sort');

        $this->saveConfiguration($configuration);

        return $app->redirectPath('admin_searchengine_get');
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        $configuration = isset($this->conf['main']['search-engine']['options']) ? $this->conf['main']['search-engine']['options'] : array();

        if (!is_array($configuration)) {
            $configuration = array();
        }

        if (!isset($configuration['date_fields'])) {
            $configuration['date_fields'] = array();
        }

        if (!isset($configuration['default_sort'])) {
            $configuration['default_sort'] = null;
        }

        return $configuration;
    }
}
