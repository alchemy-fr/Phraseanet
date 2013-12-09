<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TOU implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['controller.prod.tou'] = $this;

        $controllers = $app['controllers_factory'];

        $controllers->post('/deny/{sbas_id}/', 'controller.prod.tou:denyTermsOfUse')
            ->bind('deny_tou')
            ->before(function (Request $request) use ($app) {
                $app['firewall']->requireAuthentication();
            });

        $controllers->get('/', 'controller.prod.tou:displayTermsOfUse')
            ->bind('get_tou');

        return $controllers;
    }

    /**
     * Deny database terms of use
     *
     * @param  Application  $app
     * @param  Request      $request
     * @param  integer      $sbas_id
     * @return JsonResponse
     */
    public function denyTermsOfUse(Application $app, Request $request, $sbas_id)
    {
        $ret = ['success' => false, 'message' => ''];

        try {
            $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);

            $app['acl']->get($app['authentication']->getUser())->revoke_access_from_bases(
                array_keys($app['acl']->get($app['authentication']->getUser())->get_granted_base([], [$databox->get_sbas_id()]))
            );
            $app['acl']->get($app['authentication']->getUser())->revoke_unused_sbas_rights();

            $app['authentication']->closeAccount();

            $ret['success'] = true;
        } catch (\Exception $e) {

        }

        return $app->json($ret);
    }

    /**
     * Display database terms of use
     *
     * @param  Application $app
     * @param  Request     $request
     * @return Response
     */
    public function displayTermsOfUse(Application $app, Request $request)
    {
        $toDisplay = $request->query->get('to_display', []);
        $data = [];

        foreach ($app['phraseanet.appbox']->get_databoxes() as $databox) {
            if (count($toDisplay) > 0 && !in_array($databox->get_sbas_id(), $toDisplay)) {
                continue;
            }

            $cgus = $databox->get_cgus();

            if (!isset($cgus[$app['locale']])) {
                continue;
            }

            $data[$databox->get_label($app['locale'])] = $cgus[$app['locale']]['value'];
        }

        return new Response($app['twig']->render('/prod/TOU.html.twig', [
            'TOUs'        => $data,
            'local_title' => $app->trans('Terms of use')
        ]));
    }
}
