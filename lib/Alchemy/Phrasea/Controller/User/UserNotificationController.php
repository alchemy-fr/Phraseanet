<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Controller\User;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class UserNotificationController extends Controller
{
    /**
     * Set notifications as readed
     *
     * @param  Application  $app
     * @param  Request      $request
     * @return JsonResponse
     */
    public function readNotifications(Application $app, Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            $app->abort(400);
        }

        try {
            $app['events-manager']->read(
                explode('_', (string) $request->request->get('notifications')),
                $app['authentication']->getUser()->getId()
            );

            return $app->json(['success' => true, 'message' => '']);
        } catch (\Exception $e) {
            return $app->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Get all notifications
     *
     * @param  Application  $app
     * @param  Request      $request
     * @return JsonResponse
     */
    public function listNotifications(Application $app, Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            $app->abort(400);
        }

        $page = (int) $request->query->get('page', 0);

        return $app->json($app['events-manager']->get_notifications_as_array(($page < 0 ? 0 : $page)));
    }
}
