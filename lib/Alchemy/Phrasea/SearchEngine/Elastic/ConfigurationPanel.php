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
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\SearchEngine\AbstractConfigurationPanel;
use Symfony\Component\HttpFoundation\Request;

class ConfigurationPanel extends AbstractConfigurationPanel
{
    /** @var Application */
    private $app;

    public function __construct(Application $app, PropertyAccess $conf)
    {
        parent::__construct($conf);
        $this->app = $app;
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
    public function get(Request $request)
    {
        return $this->app['twig']->render('admin/search-engine/elastic-search.html.twig', ['configuration' => $this->getConfiguration()]);
    }

    /**
     * {@inheritdoc}
     */
    public function post(Request $request)
    {
        $configuration = $this->getConfiguration();

        $configuration['host'] = $request->request->get('host');
        $configuration['port'] = $request->request->get('port');

        $this->saveConfiguration($configuration);

        return $this->app->redirectPath('admin_searchengine_get');
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        return $this->conf->get(['main', 'search-engine', 'options'], []);
    }
}
