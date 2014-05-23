<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Root;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class Session implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        /**
         * Check session state
         *
         * name         : update_session
         *
         * description  : Check session state
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : JSON Response
         */
        $controllers->post('/update/', $this->call('updateSession'))
            ->bind('update_session');

        $controllers->post('/notifications/', $this->call('getNotifications'))
            ->bind('get_notifications');

        $controller = $controllers->post('/delete/{id}', $this->call('deleteSession'))
            ->bind('delete_session');

        $app['firewall']->addMandatoryAuthentication($controller);

        return $controllers;
    }

    /**
     * Check things to notify
     *
     * @param  Application  $app
     * @param  Request      $request
     * @return JsonResponse
     */
    public function getNotifications(Application $app, Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            $app->abort(400);
        }

        $ret = array(
            'status'  => 'unknown',
            'message' => '',
            'notifications' => false,
            'changed' => array()
        );

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

        $ret['status'] = 'ok';

        $ret['notifications'] = $app['twig']->render('prod/notifications.html.twig', array(
            'notifications' => $app['events-manager']->get_notifications()
        ));

        $baskets = $app['EM']->getRepository('\Entities\Basket')->findUnreadActiveByUser($app['authentication']->getUser());

        foreach ($baskets as $basket) {
            $ret['changed'][] = $basket->getId();
        }

        if (in_array($app['session']->get('phraseanet.message'), array('1', null))) {
            if ($app['phraseanet.configuration']['main']['maintenance']) {
                $ret['message'] .= _('The application is going down for maintenance, please logout.');
            }

            if ($app['phraseanet.registry']->get('GV_message_on')) {
                $ret['message'] .= strip_tags($app['phraseanet.registry']->get('GV_message'));
            }
        }

        return $app->json($ret);
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

        $ret = array(
            'status'  => 'unknown',
            'message' => '',
            'notifications' => false,
            'changed' => array()
        );

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

        $session = $app['EM']->find('Entities\Session', $app['session']->get('session_id'));
        $session->setUpdated(new \DateTime());

        if (!$session->hasModuleId($moduleId)) {
            $module = new \Entities\SessionModule();
            $module->setModuleId($moduleId);
            $module->setSession($session);
            $app['EM']->persist($module);
        } else {
            $app['EM']->persist($session->getModuleById($moduleId)->setUpdated(new \DateTime()));
        }

        $app['EM']->persist($session);
        $app['EM']->flush();

        $ret['status'] = 'ok';

        $ret['notifications'] = $app['twig']->render('prod/notifications.html.twig', array(
            'notifications' => $app['events-manager']->get_notifications()
        ));

        $baskets = $app['EM']->getRepository('\Entities\Basket')->findUnreadActiveByUser($app['authentication']->getUser());

        foreach ($baskets as $basket) {
            $ret['changed'][] = $basket->getId();
        }

        if (in_array($app['session']->get('phraseanet.message'), array('1', null))) {
            if ($app['phraseanet.configuration']['main']['maintenance']) {
                $ret['message'] .= _('The application is going down for maintenance, please logout.');
            }

            if ($app['phraseanet.registry']->get('GV_message_on')) {
                $ret['message'] .= strip_tags($app['phraseanet.registry']->get('GV_message'));
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
        $session = $app['EM']->find('Entities\Session', $id);

        if (null === $session) {
            $app->abort(404, 'Unknown session');
        }

        if ($session->getUsrId() !== $app['authentication']->getUser()->get_id()) {
            $app->abort(403, 'Unauthorized');
        }

        $app['EM']->remove($session);
        $app['EM']->flush();

        if ($app['request']->isXmlHttpRequest()) {
            return $app->json(array(
                'success' => true,
                'session_id' => $id
            ));
        }

        return $app->redirectPath('account_sessions');
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
