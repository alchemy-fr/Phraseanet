<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\User;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class Preferences implements ControllerProviderInterface
{

    /**
     * {@inheritDoc}
     */
    public function connect(Application $app)
    {
        $app['controller.user.preferences'] = $this;

        $controllers = $app['controllers_factory'];

        $app['firewall']->addMandatoryAuthentication($controllers);

        $controllers->post('/', 'controller.user.preferences:saveUserPref')
            ->bind('save_pref');

        $controllers->post('/temporary/', 'controller.user.preferences:saveTemporaryPref')
            ->bind('save_temp_pref');

        return $controllers;
    }

    /**
     *  Save temporary user preferences
     *
     * @param  Application  $app
     * @param  Request      $request
     * @return JsonResponse
     */
    public function saveTemporaryPref(Application $app, Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            $app->abort(400);
        }

        $prop = $request->request->get('prop');
        $value = $request->request->get('value');
        $success = false;
        $msg = $app->trans('Error while saving preference');

        if ($prop && $value) {
            $app['session']->set('phraseanet.' . $prop, $value);
            $success = true;
            $msg = $app->trans('Preference saved !');
        }

        return new JsonResponse(['success' => $success, 'message' => $msg]);
    }

    /**
     *  Save user preferenes
     *
     * @param  Application  $app
     * @param  Request      $request
     * @return JsonResponse
     */
    public function saveUserPref(Application $app, Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            $app->abort(400);
        }

        $msg = $app->trans('Error while saving preference');
        $prop = $request->request->get('prop');
        $value = $request->request->get('value');

        $success = false;
        if (null !== $prop && null !== $value) {
            $app['authentication']->getUser()->setPrefs($prop, $value);
            $success = true;
            $msg = $app->trans('Preference saved !');
        }

        return new JsonResponse(['success' => $success, 'message' => $msg]);
    }
}
