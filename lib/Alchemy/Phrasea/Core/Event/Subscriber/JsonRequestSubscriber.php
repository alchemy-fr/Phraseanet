<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\JsonResponse;

class JsonRequestSubscriber implements EventSubscriberInterface
{
    public function onSilexError(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        $request = $event->getRequest();

        if ((0 !== strpos($request->getPathInfo(), '/admin/')
            || 0 === strpos($request->getPathInfo(), '/admin/collection/')
            || 0 === strpos($request->getPathInfo(), '/admin/databox/'))
            && $request->getRequestFormat() == 'json') {
            $datas = array(
                'success' => false,
                'message' => $exception->getMessage(),
            );

            $event->setResponse(new JsonResponse($datas, 200, array('X-Status-Code' => 200)));
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(KernelEvents::EXCEPTION => array('onSilexError', 10));
    }
}
