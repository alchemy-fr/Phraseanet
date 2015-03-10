<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Admin;

use Alchemy\Phrasea\Application;
use Silex\Application as SilexApplication;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class Setup implements ControllerProviderInterface
{
    public function connect(SilexApplication $app)
    {
        $app['controller.admin.setup'] = $this;

        $controllers = $app['controllers_factory'];

        $controllers->before(function (Request $request) use ($app) {
            $app['firewall']->requireAdmin();
        });

        $controllers->match('/', 'controller.admin.setup:getGlobals')
            ->bind('setup_display_globals')
            ->method('GET|POST');

        return $controllers;
    }

    public function getGlobals(Application $app, Request $request)
    {
        $form = $app['registry.manipulator']->createForm($app['conf']);

        if ('POST' === $request->getMethod()) {
            $form->bind($request);
            if ($form->isValid()) {
                $app['conf']->set('registry', $app['registry.manipulator']->getRegistryData($form));

                return $app->redirectPath('setup_display_globals');
            }
        }

        return $app['twig']->render('admin/setup.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
