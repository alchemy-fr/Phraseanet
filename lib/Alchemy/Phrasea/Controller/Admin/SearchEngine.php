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
        $controllers = $app['controllers_factory'];

        $controllers->get('/', $this->call('getSearchEngineConfigurationPanel'))
                ->bind('admin_searchengine_get');

        $controllers->post('/', $this->call('postSearchEngineConfigurationPanel'))
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

    /**
     * Prefix the method to call with the controller class name
     *
     * @param  string $method The method to call
     * @return string
     */
    private function call($method)
    {
        return sprintf('%s::%s', __CLASS__, $method);
    }

}
