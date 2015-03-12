<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Client;

use Alchemy\Phrasea\Feed\Aggregate;
use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;
use Alchemy\Phrasea\Exception\SessionNotFound;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Root implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['controller.client'] = $this;

        $controllers = $app['controllers_factory'];

        $controllers->before(function (Request $request) use ($app) {

            if (!$app['authentication']->isAuthenticated() && null !== $request->query->get('nolog')) {
                return $app->redirectPath('login_authenticate_as_guest', ['redirect' => 'client']);
            }
            if (null !== $response = $app['firewall']->requireAuthentication()) {
                return $response;
            }
        });

        $controllers->get('/', 'controller.client:getClient')
            ->bind('get_client');

        return $controllers;
    }

    /**
     * Gets client main page
     *
     * @param  Application $app
     * @param  Request     $request
     * @return Response
     */
    public function getClient(Application $app, Request $request)
    {
        return $app->redirect($app->path('prod', array('client')));
    }
}
