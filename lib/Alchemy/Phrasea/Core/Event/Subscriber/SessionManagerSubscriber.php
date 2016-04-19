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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

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
    ];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                ['initSession', Application::EARLY_EVENT],
                ['checkSessionActivity', Application::LATE_EVENT]
            ]
        ];
    }

    public function initSession(GetResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        $request = $event->getRequest();

        if ($this->isFlashUploadRequest($request) && null !== $sessionId = $request->request->get('php_session_id')) {
            $request->cookies->set($this->app['session']->getName(), $sessionId);
        }
    }

    /**
     * log real human activity on application, to keep session alive
     * @param GetResponseEvent $event
     */
    public function checkSessionActivity(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if ($request->request->has('oauth_token')
            || $request->query->has('LOG')
            || null === $moduleId = $this->getModuleId($request->getPathInfo())
        ) {
            return;
        }

        if ($this->isAdminJsPolledRoute($moduleId, $request)) {
            return;
        }

        if ($moduleId === self::$modulesIds['prod'] && $this->isFlashUploadRequest($request)) {
            return;
        }

        // if we are already disconnected (ex. from another window), quit immediately
        if (!($this->app->getAuthenticator()->isAuthenticated())) {
            $this->setDisconnectResponse($event);

            return;
        }

        /** @var Session $session */
        $session = $this->app['repo.sessions']->find($this->app['session']->get('session_id'));

        $idle = 0;

        if (isset($this->app['phraseanet.configuration']['session']['idle'])) {
            $idle = (int)$this->app['phraseanet.configuration']['session']['idle'];
        }

        $now = new \DateTime();

        if ($idle > 0 && $now->getTimestamp() > $session->getUpdated() + $idle) {
            // we must disconnect due to idle time
            $this->app->getAuthenticator()->closeAccount();
            $this->setDisconnectResponse($event);

            return;
        }

        $entityManager = $this->app['orm.em'];

        $module = $this->addOrUpdateSessionModule($session, $moduleId, $now);

        $entityManager->persist($module);
        $entityManager->persist($session);
        $entityManager->flush();
    }

    private function isFlashUploadRequest(Request $request)
    {
        return false !== stripos($request->server->get('HTTP_USER_AGENT'), 'flash') && $request->getRequestUri() === '/prod/upload/';
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
    private function getModuleId($pathInfo)
    {
        $parts = array_filter(explode('/', $pathInfo));

        if (count($parts) < 1) {
            return null;
        }

        $moduleName = strtolower($pathInfo[1]);

        if (!isset(self::$modulesIds[$moduleName])) {
            return null;
        }

        return self::$modulesIds[$moduleName];
    }

    /**
     * @param int $moduleId
     * @param Request $request
     * @return bool
     */
    private function isAdminJsPolledRoute($moduleId, Request $request)
    {
        if ($moduleId !== self::$modulesIds['admin']) {
            return false;
        }

        $pathInfo = $request->getPathInfo();

        if ($pathInfo === '/admin/task-manager/tasks/' && $request->getContentType() === 'json') {
            return true;
        }

        return preg_match('#^/admin/databox/\d+/informations/documents/#', $pathInfo) === 1;
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
