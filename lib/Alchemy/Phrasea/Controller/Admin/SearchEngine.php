<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
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

        $controllers->get('/', function(PhraseaApplication $app, Request $request) {
            return $app['phraseanet.SE']->getConfigurationPanel($app, $request);
        })->bind('admin_searchengine_get');

        $controllers->post('/', function(PhraseaApplication $app, Request $request) {
            return $app['phraseanet.SE']->postConfigurationPanel($app, $request);
        })->bind('admin_searchengine_post');

        return $controllers;
    }
}
