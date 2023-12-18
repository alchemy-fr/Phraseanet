<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\ControllerProvider\Prod;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Controller\Prod\RootController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Alchemy\Phrasea\Model\Entities\Token;
use Alchemy\Phrasea\Model\Manipulator\TokenManipulator;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class Root implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.prod'] = $app->share(function (PhraseaApplication $app) {
            return (new RootController($app))
                ->setFirewall($app['firewall'])
            ;
        });
    }

    public function boot(Application $app)
    {
        // no-op
    }

    public function connect(Application $app)
    {
        $controllers = $this->createCollection($app);
        $controllers->before([$this, 'redirectOnLogRequests']);
        $controllers->before('controller.prod:assertAuthenticated');

        $controllers->get('/', 'controller.prod:indexAction')
            ->bind('prod');

        return $controllers;
    }

    public function redirectOnLogRequests(Request $request, PhraseaApplication $app)
    {
        if (!$request->query->has('LOG')) {
            return null;
        }

        if ($app->getAuthenticator()->isAuthenticated()) {
            $app->getAuthenticator()->closeAccount();
        }

        /** @var Token $token */
        $token = $app['repo.tokens']->findValidToken($request->query->get('LOG'));

        // actually just type user-relance can access here with token
        // PHRAS-3694
        if (null === $token || $token->getType() != TokenManipulator::TYPE_USER_RELANCE) {
            $app->addFlash('error', $app->trans('The URL you used is out of date, please login'));

            return $app->redirectPath('homepage');
        }

        /** @var Token $token */
        $app->getAuthenticator()->openAccount($token->getUser());

        return $app->redirectPath('prod');
    }
}
