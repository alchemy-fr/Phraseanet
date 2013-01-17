<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Phrasea;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\SearchEngine\AbstractConfigurationPanel;
use Symfony\Component\HttpFoundation\Request;

class ConfigurationPanel extends AbstractConfigurationPanel
{
    protected $charsets;
    protected $searchEngine;

    public function __construct(PhraseaEngine $engine)
    {
        $this->searchEngine = $engine;
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
            'date_fields'   => $this->getAvailableDateFields($app['phraseanet.appbox']->get_databoxes()),
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

        file_put_contents($this->getConfigPathFile(), json_encode($configuration));

        return $app->redirect($app['url_generator']->generate('admin_searchengine_get'));
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        $configuration = @json_decode(file_get_contents($this->getConfigPathFile()), true);

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

    /**
     * {@inheritdoc}
     */
    public function saveConfiguration(array $configuration)
    {
        file_put_contents($this->getConfigPathFile(), json_encode($configuration));
        
        return $this;
    }
}
