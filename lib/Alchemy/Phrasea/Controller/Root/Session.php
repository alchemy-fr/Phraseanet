<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Root;

use Alchemy\Phrasea\Model\Entities\SessionModule;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class Session implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['controller.session'] = $this;

        $controllers = $app['controllers_factory'];

        $controllers->post('/update/', 'controller.session:updateSession')
            ->bind('update_session');

        $controllers->post('/delete/{id}', 'controller.session:deleteSession')
            ->before(function () use ($app) {
                $app['firewall']->requireAuthentication();
            })
            ->bind('delete_session');

        return $controllers;
    }

    /**
     * Check session state
     *
     * @param  Application  $app
     * @param  Request      $request
     * @return JsonResponse
     */
    public function updateSession(Application $app, Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            $app->abort(400);
        }

        $ret = [
            'status'  => 'unknown',
            'message' => '',
            'notifications' => false,
            'changed' => []
        ];

        if ($app['authentication']->isAuthenticated()) {
            $usr_id = $app['authentication']->getUser()->get_id();
            if ($usr_id != $request->request->get('usr')) { // I logged with another user
                $ret['status'] = 'disconnected';

                return $app->json($ret);
            }
        } else {
            $ret['status'] = 'disconnected';

            return $app->json($ret);
        }

        try {
            $app['phraseanet.appbox']->get_connection();
        } catch (\Exception $e) {
            return $app->json($ret);
        }

        if (1 > $moduleId = (int) $request->request->get('module')) {
            $ret['message'] = 'Missing or Invalid `module` parameter';

            return $app->json($ret);
        }

        $session = $app['EM']->find('Alchemy\Phrasea\Model\Entities\Session', $app['session']->get('session_id'));
        $session->setUpdated(new \DateTime());

        if (!$session->hasModuleId($moduleId)) {
            $module = new SessionModule();
            $module->setModuleId($moduleId);
            $module->setSession($session);
            $app['EM']->persist($module);
        } else {
            $app['EM']->persist($session->getModuleById($moduleId)->setUpdated(new \DateTime()));
        }

        $app['EM']->persist($session);
        $app['EM']->flush();

        $ret['status'] = 'ok';

        $ret['notifications'] = $app['twig']->render('prod/notifications.html.twig', [
            'notifications' => $app['events-manager']->get_notifications()
        ]);

        $baskets = $app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\Basket')->findUnreadActiveByUser($app['authentication']->getUser());

        foreach ($baskets as $basket) {
            $ret['changed'][] = $basket->getId();
        }

        if (in_array($app['session']->get('phraseanet.message'), ['1', null])) {
            if ($app['conf']->get(['main', 'maintenance'])) {
                $ret['message'] .= $app->trans('The application is going down for maintenance, please logout.');
            }

            if ($app['conf']->get(['registry', 'maintenance', 'enabled'])) {
                $ret['message'] .= strip_tags($app['conf']->get(['registry', 'maintenance', 'message']));
            }
        }

        return $app->json($ret);
    }

    /**
     * Deletes identified session
     *
     * @param Application $app
     * @param Request     $request
     * @param integer     $id
     *
     * @return RedirectResponse|JsonResponse
     */
    public function deleteSession(Application $app, Request $request, $id)
    {
        $session = $app['EM']->find('Alchemy\Phrasea\Model\Entities\Session', $id);

        if (null === $session) {
            $app->abort(404, 'Unknown session');
        }

        if ($session->getUsrId() !== $app['authentication']->getUser()->get_id()) {
            $app->abort(403, 'Unauthorized');
        }

        $app['EM']->remove($session);
        $app['EM']->flush();

        if ($app['request']->isXmlHttpRequest()) {
            return $app->json([
                'success' => true,
                'session_id' => $id
            ]);
        }

        return $app->redirectPath('account_sessions');
    }
}
