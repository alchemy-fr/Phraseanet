<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Cors;

use Alchemy\Cors\Options\Resolver;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CorsListener
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

    protected $dispatcher;
    protected $options;
    /** @var Resolver */
    protected $configurationResolver;

    public function __construct(EventDispatcherInterface $dispatcher, Resolver $configurationResolver)
    {
        $this->dispatcher = $dispatcher;
        $this->configurationResolver = $configurationResolver;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Skip if not a CORS request
        if (!$request->headers->has('Origin') || $request->headers->get('Origin') == $request->getSchemeAndHttpHost()) {
            return;
        }

        $options = $this->configurationResolver->getOptionsFor($request);

        if (!$options) {
            return;
        }

        if ('OPTIONS' === $request->getMethod()) {
            $event->setResponse($this->getPreflightResponse($request, $options));

            return;
        }

        if (!$this->checkOrigin($request, $options)) {
            return;
        }

        $this->dispatcher->addListener(KernelEvents::RESPONSE, array($this, 'onKernelResponse'));
        $this->options = $options;
    }

    public function onKernelResponse(ResponseEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        $request = $event->getRequest();

        $response->headers->set('Access-Control-Allow-Origin', $request->headers->get('Origin'));
        if ($this->options['allow_credentials']) {
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }
        if ($this->options['expose_headers']) {
            $response->headers->set(
                'Access-Control-Expose-Headers',
                strtolower(implode(', ', $this->options['expose_headers']))
            );
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
            $headers = $options['allow_headers'] === array('*')
                ? $request->headers->get('Access-Control-Request-Headers')
                : implode(', ', $options['allow_headers']);
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
        if (!in_array(strtoupper((string) $request->headers->get('Access-Control-Request-Method')), $options['allow_methods'], true)) {
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
        if (array('*') !== $options['allow_headers']) {
            $headers = trim(strtolower((string) $headers));
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

    private function checkOrigin(Request $request, array $options)
    {
        $origin = $request->headers->get('Origin');

        if (array('*') === $options['allow_origin']) {
            return true;
        }

        if ($options['origin_regex']) {
            foreach ($options['allow_origin'] as $originRegex) {
                if (preg_match('{'.$originRegex.'}i', (string) $origin)) {
                    return true;
                }
            }

            return false;
        }

        return in_array($origin, $options['allow_origin']);
    }
}
