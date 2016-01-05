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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Response;

class BridgeExceptionSubscriber implements EventSubscriberInterface
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function onSilexError(GetResponseForExceptionEvent $event)
    {
        if (!$event->getException() instanceof \Bridge_Exception) {
            return;
        }

        $e = $event->getException();
        $request = $event->getRequest();

        $params = [
            'account'        => null,
            'elements'       => [],
            'message'        => $e->getMessage(),
            'error_message'  => null,
            'notice_message' => null,
            'file'           => $e->getFile(),
            'line'           => $e->getLine(),
            'r_method'       => $request->getMethod(),
            'r_action'       => $request->getRequestUri(),
            'r_parameters'   => ($request->getMethod() == 'GET' ? [] : $request->request->all()),
        ];

        if ($e instanceof \Bridge_Exception_ApiConnectorNotConfigured) {
            $params = array_replace($params, ['account' => $this->app['bridge.account']]);
            $response = new Response($this->app['twig']->render('/prod/actions/Bridge/notconfigured.html.twig', $params), 200, ['X-Status-Code' => 200]);
        } elseif ($e instanceof \Bridge_Exception_ApiConnectorNotConnected) {
            $params = array_replace($params, ['account' => $this->app['bridge.account']]);
            $response = new Response($this->app['twig']->render('/prod/actions/Bridge/disconnected.html.twig', $params), 200, ['X-Status-Code' => 200]);
        } elseif ($e instanceof \Bridge_Exception_ApiConnectorAccessTokenFailed) {
            $params = array_replace($params, ['account' => $this->app['bridge.account']]);
            $response = new Response($this->app['twig']->render('/prod/actions/Bridge/disconnected.html.twig', $params), 200, ['X-Status-Code' => 200]);
        } elseif ($e instanceof \Bridge_Exception_ApiDisabled) {
            $params = array_replace($params, ['api' => $e->get_api()]);
            $response = new Response($this->app['twig']->render('/prod/actions/Bridge/deactivated.html.twig', $params), 200, ['X-Status-Code' => 200]);
        } else {
            $response = new Response($this->app['twig']->render('/prod/actions/Bridge/error.html.twig', $params), 200, ['X-Status-Code' => 200]);
        }

        $response->headers->set('Phrasea-StatusCode', 200);

        $event->setResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [KernelEvents::EXCEPTION => ['onSilexError', 20]];
    }
}
