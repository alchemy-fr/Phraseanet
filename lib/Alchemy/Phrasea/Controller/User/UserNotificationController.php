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

use Alchemy\Phrasea\Application\Helper\EntityManagerAware;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Model\Repositories\BasketRepository;
use Alchemy\Phrasea\Utilities\Stopwatch;
use eventsmanager_broker;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class UserNotificationController extends Controller
{
    use EntityManagerAware;

    /**
     * Check things to notify
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function getNotifications(Request $request)
    {
        $stopwatch = new Stopwatch('notif');

        if (!$request->isXmlHttpRequest()) {
            $this->app->abort(400);
        }

        $ret = [
            'status'  => 'unknown',
            'message' => '',
            'notifications' => [],
            'unread_basket_ids' => []
        ];

        $authenticator = $this->getAuthenticator();

        if (!$authenticator->isAuthenticated()) {
            $ret['status'] = 'disconnected';

            return $this->app->json($ret);
        }

        try {
            $this->getApplicationBox()->get_connection();
        }
        catch (Exception $e) {
            return $this->app->json($ret);
        }

        $ret['status'] = 'ok';

        $stopwatch->lap("start");

        // get notifications from "notifications" table
        //

        $offset = (int)$request->get('offset', 0);
        $limit  = (int)$request->get('limit', 10);
        $what   = (int)$request->get('what', eventsmanager_broker::UNREAD | eventsmanager_broker::READ);

        $notifications = $this->getEventsManager()->get_notifications($offset, $limit, $what, $stopwatch);

        $stopwatch->lap("get_notifications done");

        // add html to each notif
        foreach ($notifications['notifications'] as $k => $v) {
            $notifications['notifications'][$k]['html'] = $this->render('prod/notification.html.twig', [
                'notification' => $v
                ]
            );
        }

        $ret['notifications'] = $notifications;

        $stopwatch->lap("render done");


        // get unread baskets
        //

        $baskets = $this->getBasketRepository()->findUnreadActiveByUser($authenticator->getUser());

        $stopwatch->lap("baskets::findUnreadActiveByUser done");

        foreach ($baskets as $basket) {
            $ret['unread_basket_ids'][] = $basket->getId();
        }


        // add message about maintenance
        //

        if (in_array($this->getSession()->get('phraseanet.message'), ['1', null])) {
            if ($this->app['phraseanet.configuration']['main']['maintenance']) {
                $ret['message'] .= $this->app->trans('The application is going down for maintenance, please logout.');
            }

            if ($this->getConf()->get(['registry', 'maintenance', 'enabled'], false)) {
                $ret['message'] .= strip_tags($this->getConf()->get(['registry', 'maintenance', 'message']));
            }
        }

        $stopwatch->lap("end");
        $stopwatch->stop();

        $response = new JsonResponse($ret);

        // add specific timing debug
        $response->headers->set('Server-Timing', $stopwatch->getLapsesAsServerTimingHeader(), false);
        $response->setCharset('UTF-8');

        // add general timing debug
        $duration = (microtime(true) - $request->server->get('REQUEST_TIME_FLOAT')) * 1000.0;
        $h = '_global;' . 'dur=' . $duration;
        $response->headers->set('Server-Timing', $h, false);    // false : add header (don't replace)

        return $response;
    }


    /**
     * patch a notification
     * for now the only usefull thing is to mark it as "read"
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function patchNotification(Request $request, $notification_id)
    {
        if (!$request->isXmlHttpRequest()) {
            $this->app->abort(400);
        }

        if($request->get('read', '0') === '1') {
            // mark as read
            try {
                $this->getEventsManager()->read(
                    [$notification_id],
                    $this->getAuthenticatedUser()->getId()
                );

                return $this->app->json(['success' => true, 'message' => '']);
            }
            catch (\Exception $e) {
                return $this->app->json(['success' => false, 'message' => $e->getMessage()]);
            }
        }
    }

    /**
     * mark all notification as read
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function readAllNotification(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            $this->app->abort(400);
        }

        try {
            $this->getEventsManager()->readAll($this->getAuthenticatedUser()->getId());

            return $this->app->json(['success' => true, 'message' => '']);
        }
        catch (\Exception $e) {
            return $this->app->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

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
     * @return eventsmanager_broker
     */
    /* remove in favor of existing /session/ route
    private function getEventsManager()
    {
        return $this->app['events-manager'];
    }
    */



    /**
     * @return eventsmanager_broker
     */
    private function getEventsManager()
    {
        return $this->app['events-manager'];
    }

    /**
     * @return BasketRepository
     */
    private function getBasketRepository()
    {
        /** @var BasketRepository $ret */
        $ret = $this->getEntityManager()->getRepository('Phraseanet:Basket');

        return $ret;
    }

    /**
     * @return Session
     */
    private function getSession()
    {
        return $this->app['session'];
    }


}
