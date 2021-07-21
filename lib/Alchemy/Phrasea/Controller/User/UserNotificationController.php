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
        file_put_contents(dirname(__FILE__).'/../../../../../logs/notifications.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("enter getNotifications() controller")
        ), FILE_APPEND | LOCK_EX);

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

        file_put_contents(dirname(__FILE__).'/../../../../../logs/notifications.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("")
        ), FILE_APPEND | LOCK_EX);

        $authenticator = $this->getAuthenticator();

        if (!$authenticator->isAuthenticated()) {

            file_put_contents(dirname(__FILE__).'/../../../../../logs/notifications.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
                sprintf("not authenticated, return")
            ), FILE_APPEND | LOCK_EX);

            $ret['status'] = 'disconnected';

            return $this->app->json($ret);
        }

        file_put_contents(dirname(__FILE__).'/../../../../../logs/notifications.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("authenticated as %s, continue with appbox get_connection()", $authenticator->getUser()->getId())
        ), FILE_APPEND | LOCK_EX);

        try {
            $this->getApplicationBox()->get_connection();
        }
        catch (Exception $e) {
            return $this->app->json($ret);
        }

        file_put_contents(dirname(__FILE__).'/../../../../../logs/notifications.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("connection ok")
        ), FILE_APPEND | LOCK_EX);

        $ret['status'] = 'ok';

        $stopwatch->lap("start");

        // get notifications from "notifications" table
        //

        $offset = (int)$request->get('offset', 0);
        $limit  = (int)$request->get('limit', 10);
        $what   = (int)$request->get('what', eventsmanager_broker::UNREAD | eventsmanager_broker::READ);

        file_put_contents(dirname(__FILE__).'/../../../../../logs/notifications.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("calling broker->get_notifications(offset=%s, limit=%s, what=%s)", $offset, $limit, $what)
        ), FILE_APPEND | LOCK_EX);

        $notifications = $this->getEventsManager()->get_notifications($offset, $limit, $what, $stopwatch);

        file_put_contents(dirname(__FILE__).'/../../../../../logs/notifications.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("broker->et_notifications(...) done, adding html")
        ), FILE_APPEND | LOCK_EX);

        $stopwatch->lap("get_notifications done");

        // add html to each notif
        $n = 0;
        foreach ($notifications['notifications'] as $k => $v) {
            $notifications['notifications'][$k]['html'] = $this->render('prod/notification.html.twig', [
                'notification' => $v
                ]
            );
            $n++;
        }

        file_put_contents(dirname(__FILE__).'/../../../../../logs/notifications.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("added html to %s notifications, finding unread baskets", $n)
        ), FILE_APPEND | LOCK_EX);

        $ret['notifications'] = $notifications;

        $stopwatch->lap("render done");


        // get unread baskets
        //

        $baskets = $this->getBasketRepository()->findUnreadActiveByUser($authenticator->getUser());

        file_put_contents(dirname(__FILE__).'/../../../../../logs/notifications.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("finding unread baskets done")
        ), FILE_APPEND | LOCK_EX);

        $stopwatch->lap("baskets::findUnreadActiveByUser done");

        $n = 0;
        foreach ($baskets as $basket) {
            $ret['unread_basket_ids'][] = $basket->getId();
            $n++;
        }

        file_put_contents(dirname(__FILE__).'/../../../../../logs/notifications.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("found %s unread baskets, adding maintenance messages", $n)
        ), FILE_APPEND | LOCK_EX);

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

        file_put_contents(dirname(__FILE__).'/../../../../../logs/notifications.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("maintenance message=\"%s\"", $ret['message'])
        ), FILE_APPEND | LOCK_EX);


        $stopwatch->lap("end");
        $stopwatch->stop();

        $response = new JsonResponse($ret);

        file_put_contents(dirname(__FILE__).'/../../../../../logs/notifications.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("adding timing messages to response")
        ), FILE_APPEND | LOCK_EX);


        // add specific timing debug
        $response->headers->set('Server-Timing', $stopwatch->getLapsesAsServerTimingHeader(), false);
        $response->setCharset('UTF-8');

        // add general timing debug
        $duration = (microtime(true) - $request->server->get('REQUEST_TIME_FLOAT')) * 1000.0;
        $h = '_global;' . 'dur=' . $duration;
        $response->headers->set('Server-Timing', $h, false);    // false : add header (don't replace)

        file_put_contents(dirname(__FILE__).'/../../../../../logs/notifications.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("return from controller")
        ), FILE_APPEND | LOCK_EX);

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
