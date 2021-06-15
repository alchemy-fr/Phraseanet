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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class UserNotificationController extends Controller
{
    /**
     * Set notifications as read
     *
     * @param  Request $request
     * @return JsonResponse
     */
    /* remove in favor of existing /session/ route
    public function readNotifications(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            $this->app->abort(400);
        }

        try {
            $this->getEventsManager()->read(
                explode('_', (string) $request->request->get('notifications')),
                $this->getAuthenticatedUser()->getId()
            );

            return $this->app->json(['success' => true, 'message' => '']);
        } catch (\Exception $e) {
            return $this->app->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    */

    /**
     * Get all notifications
     *
     * @param  Request $request
     * @return JsonResponse
     */
    /* remove in favor of existing /session/ route
    public function listNotifications(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            $this->app->abort(400);
        }

        $page = (int) $request->query->get('page', 0);

        return $this->app->json($this->getEventsManager()->get_notifications_as_array(($page < 0 ? 0 : $page)));
    }
    */

    /**
     * @return \eventsmanager_broker
     */
    /* remove in favor of existing /session/ route
    private function getEventsManager()
    {
        return $this->app['events-manager'];
    }
    */
}
