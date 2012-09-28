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

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class UserPreferences implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->before(function(Request $request) use ($app) {
            $response = $app['firewall']->requireAuthentication();

            if ($response instanceof Response) {
                return $response;
            }
        });

        $controllers->post('/save/', $this->call('savePreference'));

        return $controllers;
    }

    public function savePreference(Application $app, Request $request)
    {
        $ret = array('success' => false, 'message' => _('Error while saving preference'));

        try {
            $user = $app['phraseanet.user'];

            $ret = $user->setPrefs($request->request->get('prop'), $request->request->get('value'));

            if ($ret == $request->request->get('value'))
                $output = "1"; else
                $output = "0";

            $ret = array('success' => true, 'message' => _('Preference saved !'));
        } catch (\Exception $e) {

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
