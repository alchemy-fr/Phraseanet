<?php

namespace Alchemy\Cors\Tests;

use Alchemy\Cors\CorsListener;
use Alchemy\Cors\Options\DefaultProvider;
use Alchemy\Cors\Options\DefaultResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class CorsListenerTest extends TestCase
{
    private const OPTIONS = [
        'allow_credentials' => true,
        'allow_origin' => ['http://allowed.example'],
        'origin_regex' => false,
        'allow_headers' => ['x-custom'],
        'allow_methods' => ['GET', 'POST'],
        'expose_headers' => ['X-Exposed'],
        'max_age' => 3600,
        'hosts' => [],
    ];

    public function testNonCorsRequestsAreIgnored()
    {
        $dispatcher = new EventDispatcher();
        $listener = $this->createListener($dispatcher);
        $event = $this->createRequestEvent(Request::create('http://localhost/api/'));

        $listener->onKernelRequest($event);

        $this->assertNull($event->getResponse());
        $this->assertFalse($dispatcher->hasListeners(KernelEvents::RESPONSE));
    }

    public function testPreflightRequestOfAllowedOrigin()
    {
        $request = Request::create('http://localhost/api/', 'OPTIONS');
        $request->headers->set('Origin', 'http://allowed.example');
        $request->headers->set('Access-Control-Request-Method', 'POST');
        $request->headers->set('Access-Control-Request-Headers', 'X-Custom, Accept');

        $event = $this->createRequestEvent($request);
        $this->createListener(new EventDispatcher())->onKernelRequest($event);

        $response = $event->getResponse();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('http://allowed.example', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertSame('true', $response->headers->get('Access-Control-Allow-Credentials'));
        $this->assertSame('GET, POST', $response->headers->get('Access-Control-Allow-Methods'));
        $this->assertSame('x-custom', $response->headers->get('Access-Control-Allow-Headers'));
        $this->assertSame('3600', $response->headers->get('Access-Control-Max-Age'));
    }

    public function testPreflightRequestWithoutRequestedMethodOrHeaders()
    {
        $request = Request::create('http://localhost/api/', 'OPTIONS');
        $request->headers->set('Origin', 'http://allowed.example');

        $event = $this->createRequestEvent($request);
        $this->createListener(new EventDispatcher())->onKernelRequest($event);

        $this->assertSame(405, $event->getResponse()->getStatusCode());
    }

    public function testPreflightRequestOfUnknownOrigin()
    {
        $request = Request::create('http://localhost/api/', 'OPTIONS');
        $request->headers->set('Origin', 'http://evil.example');
        $request->headers->set('Access-Control-Request-Method', 'GET');

        $event = $this->createRequestEvent($request);
        $this->createListener(new EventDispatcher())->onKernelRequest($event);

        $this->assertSame('null', $event->getResponse()->headers->get('Access-Control-Allow-Origin'));
    }

    public function testPreflightRequestWithUnauthorizedHeader()
    {
        $request = Request::create('http://localhost/api/', 'OPTIONS');
        $request->headers->set('Origin', 'http://allowed.example');
        $request->headers->set('Access-Control-Request-Method', 'GET');
        $request->headers->set('Access-Control-Request-Headers', 'X-Forbidden');

        $event = $this->createRequestEvent($request);
        $this->createListener(new EventDispatcher())->onKernelRequest($event);

        $this->assertSame(400, $event->getResponse()->getStatusCode());
        $this->assertSame('Unauthorized header x-forbidden', $event->getResponse()->getContent());
    }

    public function testActualRequestGetsCorsHeadersOnResponse()
    {
        $dispatcher = new EventDispatcher();
        $listener = $this->createListener($dispatcher);

        $request = Request::create('http://localhost/api/');
        $request->headers->set('Origin', 'http://allowed.example');

        $requestEvent = $this->createRequestEvent($request);
        $listener->onKernelRequest($requestEvent);

        $this->assertNull($requestEvent->getResponse());
        $this->assertTrue($dispatcher->hasListeners(KernelEvents::RESPONSE));

        $responseEvent = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new Response()
        );
        $dispatcher->dispatch($responseEvent, KernelEvents::RESPONSE);

        $response = $responseEvent->getResponse();
        $this->assertSame('http://allowed.example', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertSame('true', $response->headers->get('Access-Control-Allow-Credentials'));
        $this->assertSame('x-exposed', $response->headers->get('Access-Control-Expose-Headers'));
    }

    public function testSubRequestsAreIgnored()
    {
        $request = Request::create('http://localhost/api/', 'OPTIONS');
        $request->headers->set('Origin', 'http://allowed.example');

        $event = new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::SUB_REQUEST);
        $this->createListener(new EventDispatcher())->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testOriginRegex()
    {
        $options = array_merge(self::OPTIONS, ['origin_regex' => true, 'allow_origin' => ['^https?://.*\.allowed\.example$']]);
        $listener = new CorsListener(new EventDispatcher(), new DefaultResolver([new DefaultProvider(['^/api/' => $options], [])]));

        $request = Request::create('http://localhost/api/', 'OPTIONS');
        $request->headers->set('Origin', 'https://sub.allowed.example');
        $request->headers->set('Access-Control-Request-Method', 'GET');

        $event = $this->createRequestEvent($request);
        $listener->onKernelRequest($event);

        $this->assertSame('https://sub.allowed.example', $event->getResponse()->headers->get('Access-Control-Allow-Origin'));
    }

    private function createListener(EventDispatcher $dispatcher)
    {
        return new CorsListener($dispatcher, new DefaultResolver([new DefaultProvider(['^/api/' => self::OPTIONS], [])]));
    }

    private function createRequestEvent(Request $request)
    {
        return new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
    }
}
