<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
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

        return $controllers;
    }

    /**
     * Check session state
     *
     * @param   Application $app
     * @param   Request     $request
     * @return  JsonResponse
     */
    public function updateSession(Application $app, Request $request)
    {
        if(!$request->isXmlHttpRequest()) {
            $app->abort(400);
        }

        $ret = array(
            'status'  => 'unknown',
            'message' => '',
            'notifications' => false,
            'changed' => array()
        );

        if ($app->isAuthenticated()) {
            $usr_id = $app['phraseanet.user']->get_id();
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

        $session = $app['EM']->find('Entities\Session', $app['session']->get('session_id'));

        if (!$session->hasModuleId($moduleId = $request->request->get('module'))) {
            $module = new \Entities\SessionModule();
            $module->setModuleId($moduleId);
            $module->setSession($session);
            $app['EM']->persist($module);
            $app['EM']->persist($session);
        }

        $ret['status'] = 'ok';

        $ret['notifications'] = $app['twig']->render('prod/notifications.html.twig', array(
            'notifications' => $app['events-manager']->get_notifications()
        ));

        $baskets = $app['EM']->getRepository('\Entities\Basket')->findUnreadActiveByUser($app['phraseanet.user']);

        foreach ($baskets as $basket) {
            $ret['changed'][] = $basket->getId();
        }

        if (in_array($app['session']->get('phraseanet.message'), array('1', null))) {
            if ($app['phraseanet.registry']->get('GV_maintenance')) {

                $ret['message'] .= _('The application is going down for maintenance, please logout.');
            }

            if ($app['phraseanet.registry']->get('GV_message_on')) {

                $ret['message'] .= strip_tags($app['phraseanet.registry']->get('GV_message'));
            }
        }

        return $app->json($ret);
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
