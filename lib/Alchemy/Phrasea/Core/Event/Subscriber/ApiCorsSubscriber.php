<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Application;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class ApiCorsSubscriber implements EventSubscriberInterface
{
    /**
     * Simple headers as defined in the spec should always be accepted
     */
    protected static $simpleHeaders = array(
        'accept',
        'accept-language',
        'content-language',
        'origin',
    );

    private $app;
    private $options;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => array('onKernelRequest', 128),
        );
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$this->app['phraseanet.configuration']['api_cors']['enabled']) {
            return;
        }
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }
        $request = $event->getRequest();

        if (!preg_match('{api/v(\d+)}i', $request->getPathInfo() ?: '/')) {
            return;
        }

        // skip if not a CORS request
        if (!$request->headers->has('Origin') || $request->headers->get('Origin') == $request->getSchemeAndHttpHost()) {
            return;
        }

        $options = array_merge(array(
            'allow_credentials'=> false,
            'allow_origin'=> array(),
            'allow_headers'=> array(),
            'allow_methods'=> array(),
            'expose_headers'=> array(),
            'max_age'=> 0,
            'hosts'=> array(),
        ), $this->app['phraseanet.configuration']['api_cors']);

        // skip if the host is not matching
        if (!$this->checkHost($request, $options)) {
            return;
        }
        // perform preflight checks
        if ('OPTIONS' === $request->getMethod()) {
            $event->setResponse($this->getPreflightResponse($request, $options));

            return;
        }
        if (!$this->checkOrigin($request, $options)) {
            $response = new Response('', 403, array('Access-Control-Allow-Origin' => 'null'));
            $event->setResponse($response);

            return;
        }

        $this->app['dispatcher']->addListener(KernelEvents::RESPONSE, array($this, 'onKernelResponse'));
        $this->options = $options;
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        $response = $event->getResponse();
        $request = $event->getRequest();
        // add CORS response headers
        $response->headers->set('Access-Control-Allow-Origin', $request->headers->get('Origin'));
        if ($this->options['allow_credentials']) {
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }
        if ($this->options['expose_headers']) {
            $response->headers->set('Access-Control-Expose-Headers', strtolower(implode(', ', $this->options['expose_headers'])));
        }
    }

    protected function getPreflightResponse(Request $request, array $options)
    {
        $response = new Response();

        if ($options['allow_credentials']) {
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }
        if ($options['allow_methods']) {
            $response->headers->set('Access-Control-Allow-Methods', implode(', ', $options['allow_methods']));
        }
        if ($options['allow_headers']) {
            $headers = in_array('*', $options['allow_headers'])
                ? $request->headers->get('Access-Control-Request-Headers')
                : implode(', ', array_map('strtolower', $options['allow_headers']));
            $response->headers->set('Access-Control-Allow-Headers', $headers);
        }
        if ($options['max_age']) {
            $response->headers->set('Access-Control-Max-Age', $options['max_age']);
        }

        if (!$this->checkOrigin($request, $options)) {
            $response->headers->set('Access-Control-Allow-Origin', 'null');

            return $response;
        }

        $response->headers->set('Access-Control-Allow-Origin', $request->headers->get('Origin'));

        // check request method
        if (!in_array(strtoupper($request->headers->get('Access-Control-Request-Method')), $options['allow_methods'], true)) {
            $response->setStatusCode(405);

            return $response;
        }

        /**
         * We have to allow the header in the case-set as we received it by the client.
         * Firefox f.e. sends the LINK method as "Link", and we have to allow it like this or the browser will deny the
         * request.
         */
        if (!in_array($request->headers->get('Access-Control-Request-Method'), $options['allow_methods'], true)) {
            $options['allow_methods'][] = $request->headers->get('Access-Control-Request-Method');
            $response->headers->set('Access-Control-Allow-Methods', implode(', ', $options['allow_methods']));
        }

        // check request headers
        $headers = $request->headers->get('Access-Control-Request-Headers');
        if ($options['allow_headers'] !== true && $headers) {
            $headers = trim(strtolower($headers));
            foreach (preg_split('{, *}', $headers) as $header) {
                if (in_array($header, self::$simpleHeaders, true)) {
                    continue;
                }
                if (!in_array($header, $options['allow_headers'], true)) {
                    $response->setStatusCode(400);
                    $response->setContent('Unauthorized header '.$header);
                    break;
                }
            }
        }

        return $response;
    }

    protected function checkOrigin(Request $request, array $options)
    {
        // check origin
        $origin = $request->headers->get('Origin');
        if (in_array('*', $options['allow_origin']) || in_array($origin, $options['allow_origin'])) {
            return true;
        }

        return false;
    }

    protected function checkHost(Request $request, array $options)
    {
        if (count($options['hosts']) === 0) {
            return true;
        }

        foreach ($options['hosts'] as $hostRegexp) {
            if (preg_match('{'.$hostRegexp.'}i', $request->getHost())) {
                return true;
            }
        }

        return false;
    }
}
