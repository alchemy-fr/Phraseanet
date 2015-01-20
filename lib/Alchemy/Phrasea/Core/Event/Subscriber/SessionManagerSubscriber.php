<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Application;
use Entities\SessionModule;
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
        return array(
            KernelEvents::REQUEST => array(
                array('initSession', Application::EARLY_EVENT),
                array('checkSessionActivity', Application::LATE_EVENT)
            )
        );
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

                return $request;
            }
        }
    }

    /**log real human activity on application, to keep session alive*/
    public function checkSessionActivity(GetResponseEvent $event)
    {
        $modulesIds = array(
            "prod"      => 1,
            "client"    => 2,
            "admin"     => 3,
            "thesaurus" => 5,
            "report"    => 10,
            "lightbox"  => 6,
        );

        $pathInfo = array_filter(explode('/', $event->getRequest()->getPathInfo()));

        if (count($pathInfo) < 1) {
            return;
        }
        $moduleName = strtolower($pathInfo[1]);

        if (!array_key_exists($moduleName, $modulesIds) ) {
            return;
        }
        // this route is polled by js in admin/databox to refresh infos (progress bar...)
        if (preg_match("#^/admin/databox/[0-9]+/informations/documents/#", $event->getRequest()->getPathInfo()) == 1) {
            return;
        }
        // this route is polled by js in admin/tasks to refresh tasks status
        if ($event->getRequest()->getPathInfo() == "/admin/task-manager/tasks/" && $event->getRequest()->getContentType() == 'json') {
            return;
        }

        if ($this->isFlashUploadRequest($event->getRequest())) {
            return;
        }

        if ($event->getRequest()->query->has('LOG')) {
            return;
        }

        // if we are already disconnected (ex. from another window), quit immediatly
        if (!($this->app['authentication']->isAuthenticated())) {
            if ($event->getRequest()->isXmlHttpRequest()) {
                $response = new Response("End-Session", 403);
            } else {
                $response = new RedirectResponse($this->app["url_generator"]->generate("homepage", array("redirect"=>'..' . $event->getRequest()->getPathInfo())));
            }
            $response->headers->set('X-Phraseanet-End-Session', '1');

            $event->setResponse($response);

            return;
        }
        $session = $this->app['EM']->find('Entities\Session', $this->app['session']->get('session_id'));

        $idle = 0;
        if (isset($this->app["phraseanet.configuration"]["session"]["idle"])) {
            $idle = (int) ($this->app["phraseanet.configuration"]["session"]["idle"]);
        }
        $now = new \DateTime();
        $dt = $now->getTimestamp() - $session->getUpdated()->getTimestamp();
        if ($idle > 0 && $dt > $idle) {
            // we must disconnet due to idletime
            $this->app['authentication']->closeAccount();
            if ($event->getRequest()->isXmlHttpRequest()) {
                $response = new Response("End-Session", 403);
            } else {
                $response = new RedirectResponse($this->app["url_generator"]->generate("homepage", array("redirect"=>'..' . $event->getRequest()->getPathInfo())));
            }
            $response->headers->set('X-Phraseanet-End-Session', '1');

            $event->setResponse($response);

            return;
        }
        $moduleId = $modulesIds[$moduleName];

        $session->setUpdated(new \DateTime());

        if (!$session->hasModuleId($moduleId)) {
            $module = new SessionModule();
            $module->setModuleId($moduleId);
            $module->setSession($session);
            $session->addModule($module);

            $this->app['EM']->persist($module);
        } else {
            $this->app['EM']->persist($session->getModuleById($moduleId)->setUpdated(new \DateTime()));
        }
        $this->app['EM']->persist($session);
        $this->app['EM']->flush();
    }

    private function isFlashUploadRequest(Request $request)
    {
        return false !== stripos($request->server->get('HTTP_USER_AGENT'), 'flash')
        && $request->getRequestUri() === '/prod/upload/';
    }
}
