<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Admin;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Symfony\Component\HttpFoundation\Request;
use Silex\Application;
use Silex\ControllerProviderInterface;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class SearchEngine implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $app['controller.admin.search-engine'] = $this;

        $controllers = $app['controllers_factory'];

        $controllers->get('/', 'controller.admin.search-engine:getSearchEngineConfigurationPanel')
                ->bind('admin_searchengine_get');

        $controllers->post('/', 'controller.admin.search-engine:postSearchEngineConfigurationPanel')
                ->bind('admin_searchengine_post');

        return $controllers;
    }

    public function getSearchEngineConfigurationPanel(PhraseaApplication $app, Request $request)
    {
        return $app['phraseanet.SE']->getConfigurationPanel()->get($app, $request);
    }

    public function postSearchEngineConfigurationPanel(PhraseaApplication $app, Request $request)
    {
        return $app['phraseanet.SE']->getConfigurationPanel()->post($app, $request);
    }
}
