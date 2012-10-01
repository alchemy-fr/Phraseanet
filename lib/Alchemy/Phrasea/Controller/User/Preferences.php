<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Preferences implements ControllerProviderInterface
{

    /**
     * {@inheritDoc}
     */
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
        $controllers->before(function(Request $request) use ($app) {
            $response = $app['firewall']->requireAuthentication();
            if ($response instanceof Response) {
                return $response;
            }
        });

        /**
         * Save preferences
         *
         * name         : save_pref
         *
         * description  : Save User preferences
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : JSON Response
         */
        $controllers->post('/', $this->call('saveUserPref'))
            ->bind('save_pref');

        /**
         * Save CSS preferences
         *
         * name         : save_css_pref
         *
         * description  : Save CSS preferences
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : JSON Response
         */
        $controllers->post('/temporary/', $this->call('saveTemporaryPref'))
            ->bind('save_css_pref');

        return $controllers;
    }

    /**
     *  Save user production preferenes
     *
     * @param   Application     $app
     * @param   Request         $request
     * @return  JsonResponse
     */
    public function saveTemporaryPref(Application $app, Request $request)
    {
        $prop = $request->request->get('prop');
        $value = $request->request->get('value');
        $success = false;

        if($prop && $value) {
            $app['session']->set('pref.' . $prop, $value);
            $success = true;
        }

        return new JsonResponse(array('success' => $success));
    }

    /**
     *  Save css production preferenes
     *
     * @param   Application     $app
     * @param   Request         $request
     * @return  JsonResponse
     */
    public function saveUserPref(Application $app, Request $request)
    {
        $prop = $request->request->get('prop');
        $value = $request->request->get('value');

        $success = false;

        if($prop && $value) {
            $success = ! ! $app['phraseanet.user']->setPrefs($prop, $value);
        }

        return new JsonResponse(array('success' => $success));
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
