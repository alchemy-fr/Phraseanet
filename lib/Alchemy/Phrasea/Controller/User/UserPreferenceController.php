<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Controller\User;

use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Model\Manipulator\UserManipulator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class UserPreferenceController extends Controller
{
    /**
     *  Save temporary user preferences
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function saveTemporaryPref(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            $this->app->abort(400);
        }

        $prop = $request->request->get('prop');
        $value = $request->request->get('value');
        $success = false;
        $msg = $this->app->trans('Error while saving preference');

        if (!is_null($prop) && !is_null($value)) {
            $this->getSession()->set('phraseanet.' . $prop, $value);
            $success = true;
            $msg = $this->app->trans('Preference saved !');
        }

        return new JsonResponse(['success' => $success, 'message' => $msg]);
    }

    /**
     *  Save user preferences
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function saveUserPref(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            $this->app->abort(400);
        }

        $msg = $this->app->trans('Error while saving preference');
        $prop = $request->request->get('prop');
        $value = $request->request->get('value');

        $success = false;
        if (null !== $prop && null !== $value) {
            $this->getUserManipulator()->setUserSetting($this->getAuthenticatedUser(), $prop, $value);
            $success = true;
            $msg = $this->app->trans('Preference saved !');
        }

        return new JsonResponse(['success' => $success, 'message' => $msg]);
    }

    /**
     * @return Session
     */
    private function getSession()
    {
        return $this->app['session'];
    }

    /**
     * @return UserManipulator
     */
    private function getUserManipulator()
    {
        return $this->app['manipulator.user'];
    }
}
