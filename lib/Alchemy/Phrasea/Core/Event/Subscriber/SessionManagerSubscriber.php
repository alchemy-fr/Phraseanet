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

        if ($this->isFlashUploadRequest($event->getRequest())) {
            if (null !== $sessionId = $event->getRequest()->request->get('php_session_id')) {

                $request = $event->getRequest();
                $request->cookies->set($this->app['session']->getName(), $sessionId);

            }
        }
    }

    /**
     * log real human activity on application, to keep session alive
     * @param GetResponseEvent $event
     */
    public function checkSessionActivity(GetResponseEvent $event)
    {
        $modulesIds = [
            "prod"      => 1,
            "client"    => 2,
            "admin"     => 3,
            "thesaurus" => 5,
            "report"    => 10,
            "lightbox"  => 6,
        ];

        $request = $event->getRequest();

        $pathInfo = array_filter(explode('/', $request->getPathInfo()));

        if (count($pathInfo) < 1) {
            return;
        }
        $moduleName = strtolower($pathInfo[1]);

        if (!array_key_exists($moduleName, $modulesIds) ) {
            return;
        }
        // this route is polled by js in admin/databox to refresh infos (progress bar...)
        if (preg_match("#^/admin/databox/[0-9]+/informations/documents/#", $request->getPathInfo()) == 1) {
            return;
        }
        // this route is polled by js in admin/tasks to refresh tasks status
        if ($request->getPathInfo() == "/admin/task-manager/tasks/" && $request->getContentType() == 'json') {
            return;
        }

        if ($this->isFlashUploadRequest($request)) {
            return;
        }

        if ($request->query->has('LOG')) {
            return;
        }

        // if we are already disconnected (ex. from another window), quit immediately
        if (!($this->app->getAuthenticator()->isAuthenticated())) {
            $this->setDisconnectResponse($event);

            return;
        }

        $session = $this->app['repo.sessions']->find($this->app['session']->get('session_id'));

        $idle = 0;
        if (isset($this->app["phraseanet.configuration"]["session"]["idle"])) {
            $idle = (int) ($this->app["phraseanet.configuration"]["session"]["idle"]);
        }
        $now = new \DateTime();
        $dt = $now->getTimestamp() - $session->getUpdated()->getTimestamp();
        if ($idle > 0 && $dt > $idle) {
            // we must disconnect due to idle time
            $this->app->getAuthenticator()->closeAccount();
            $this->setDisconnectResponse($event);

            return;
        }
        $moduleId = $modulesIds[$moduleName];

        $session->setUpdated(new \DateTime());

        $entityManager = $this->app['orm.em'];

        if (!$session->hasModuleId($moduleId)) {
            $module = new SessionModule();
            $module->setModuleId($moduleId);
            $module->setSession($session);
            $session->addModule($module);

            $entityManager->persist($module);
        } else {
            $entityManager->persist($session->getModuleById($moduleId)->setUpdated(new \DateTime()));
        }
        $entityManager->persist($session);
        $entityManager->flush();
    }

    private function isFlashUploadRequest(Request $request)
    {
        return false !== stripos($request->server->get('HTTP_USER_AGENT'), 'flash')
        && $request->getRequestUri() === '/prod/upload/';
    }

    /**
     * @param GetResponseEvent $event
     */
    private function setDisconnectResponse(GetResponseEvent $event)
    {
        if ($event->getRequest()->isXmlHttpRequest()) {
            $response = new Response("End-Session", 403, ['X-Phraseanet-End-Session' => '1']);

            $event->setResponse($response);

            return;
        }

        $redirectUrl = $this->app["url_generator"]->generate("homepage", [
            "redirect" => '..' . $event->getRequest()->getPathInfo(),
        ]);

        $response = new RedirectResponse($redirectUrl, 302, ['X-Phraseanet-End-Session' => '1']);

        $event->setResponse($response);
    }
}
