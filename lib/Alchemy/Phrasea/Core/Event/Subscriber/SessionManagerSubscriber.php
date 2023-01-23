<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\Session;
use Alchemy\Phrasea\Model\Entities\SessionModule;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SessionManagerSubscriber implements EventSubscriberInterface
{
    private $app;

    private static $modulesIds = [
        'prod' => 1,
        'client' => 2,
        'admin' => 3,
        'thesaurus' => 5,
        'report' => 10,
        'lightbox' => 6,
        'user' => 7,
    ];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                /** @uses SessionManagerSubscriber::checkSessionActivity */
                ['checkSessionActivity', Application::LATE_EVENT]
            ]
        ];
    }

    /**
     * log real human activity on application, to keep session alive
     *
     * to "auto-disconnect" when idle duration is passed, we use the "poll" requests.
     * nb : the route "/sessions/notifications" is not considered as comming from a "module" (prod, admin, ...)
     *      so it will not update session
     *
     * @param GetResponseEvent $event
     */
    public function checkSessionActivity(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        // ignore routes that comes from api (?) : todo : check if usefull since "api" is not a "module"
        // todo : "LOG" ???
        if ($request->request->has('oauth_token')
            || $request->query->has('oauth_token')
            || $request->query->has('LOG')
        ) {
            return;
        }

        $moduleName= $this->getModuleName($request->getPathInfo());
        $moduleId = $this->getModuleId($request->getPathInfo());

        // "unknown" modules do not keep session alive, nor close session, nor redirect to login
        //
        if(is_null($moduleId)) {
            return;
        }

        // any other route can redirect to login if user is diconnected

        // if we are already disconnected (ex. from another window), quit immediately
        //
        if (!($this->app->getAuthenticator()->isAuthenticated())) {
            $this->setDisconnectResponse($event);
            return;
        }

        if(!is_null($h_usr_id = $request->headers->get('user-id'))) {
            $a_usr_id = $this->app->getAuthenticator()->getUser()->getId();
            if((int)$h_usr_id !== (int)$a_usr_id) {
                $this->setDisconnectResponse($event);
                return;
            }
        }

        // ANY route can disconnect the user if idle duration is passed
        //
        /** @var Session $session */
        $session = $this->app['repo.sessions']->find($this->app['session']->get('session_id'));

        $idle = 0;

        if (isset($this->app['phraseanet.configuration']['session']['idle'])) {
            $idle = (int)$this->app['phraseanet.configuration']['session']['idle'];
        }

        $now = new \DateTime();

        if ($idle > 0 && $now->getTimestamp() > $session->getUpdated()->getTimestamp() + $idle) {
            // we must disconnect due to idle time
            $this->app->getAuthenticator()->closeAccount();
            $this->setDisconnectResponse($event);

            return;
        }

        // only routes from "modules" (prod, admin, ...) are considered as "user activity"
        // we must still ignore some "polling" (js) routes
        //
        if ($this->isJsPollingRoute($request)) {
            return;
        }

        // here the route is considered as "user activity" : update session
        //
        $entityManager = $this->app['orm.em'];

        $module = $this->addOrUpdateSessionModule($session, $moduleId, $now);

        $entityManager->persist($module);
        $entityManager->persist($session);
        $entityManager->flush();
    }

    /**
     * @param GetResponseEvent $event
     */
    private function setDisconnectResponse(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        $response = $request->isXmlHttpRequest() ? $this->getXmlHttpResponse() : $this->getRedirectResponse($request);
        $event->setResponse($response);
    }

    /**
     * @return Response
     */
    private function getXmlHttpResponse()
    {
        return new Response("End-Session", 403, ['X-Phraseanet-End-Session' => '1']);
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    private function getRedirectResponse(Request $request)
    {
        $redirectUrl = $this->app['url_generator']->generate('homepage', [
            'redirect' => '..' . $request->getPathInfo(),
        ]);

        return new RedirectResponse($redirectUrl, 302, ['X-Phraseanet-End-Session' => '1']);
    }

    /**
     * @param string $pathInfo
     * @return int|null
     */
    private function getModuleName($pathInfo)
    {
        $parts = array_filter(explode('/', $pathInfo));

        if (count($parts) < 1) {
            return null;
        }

        return strtolower($parts[1]);
    }

    /**
     * @param string $pathInfo
     * @return int|null
     */
    private function getModuleId($pathInfo)
    {
        $moduleName = $this->getModuleName($pathInfo);

        if (!isset(self::$modulesIds[$moduleName])) {
            return null;
        }

        return self::$modulesIds[$moduleName];
    }

    /**
     * returns true is the route match a "polling" route (databox progressionbar, task manager, ...)
     * polling routes (sent every n seconds with no user action) must not update the session
     *
     * the request should contain a "update-session=0" header, but for now we still test hardcoded routes
     *
     * @param int $moduleId
     * @param Request $request
     * @return bool
     */
    private function isJsPollingRoute(Request $request)
    {
        if($request->headers->get('update-session', '1') === '0') {
            return true;
        }

        $pathInfo = $request->getPathInfo();

        // nutifications poll in menubar (header "update-session=0") sent
        if ($pathInfo === '/user/notifications/' && $request->getContentType() === 'json') {
            return true;
        }

        // admin/task managers poll tasks
        if ($pathInfo === '/admin/task-manager/tasks/' && $request->getContentType() === 'json') {
            return true;
        }

        // admin/databox poll to update the indexation progress bar (header "update-session=0") sent
        if(preg_match('#^/admin/databox/\d+/informations/documents/#', $pathInfo)) {
            return true;
        }

        return false;
    }

    /**
     * @param Session $session
     * @param int $moduleId
     * @param \DateTime $now
     * @return SessionModule
     */
    private function addOrUpdateSessionModule(Session $session, $moduleId, \DateTime $now)
    {
        $session->setUpdated($now);

        if (null !== $module = $session->getModuleById($moduleId)) {
            return $module->setUpdated($now);
        }

        $module = new SessionModule();

        $module->setCreated($now);
        $module->setModuleId($moduleId);
        $module->setSession($session);

        $session->addModule($module);

        return $module;
    }
}
