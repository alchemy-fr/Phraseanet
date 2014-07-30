<?php

namespace Alchemy\Tests\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Event\Subscriber\ContentNegotiationSubscriber;
use Symfony\Component\HttpKernel\Client;

class ContentNegotiationSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider negotiationProvider
     */
    public function testContentNegotiationProvider($negotiationHeader, $expectedContentType)
    {
        $response = $this->request($negotiationHeader, $expectedContentType);
        $this->assertArrayHasKey('content-type', $response->headers->all());
        $this->assertEquals($expectedContentType, $response->headers->get('content-type'));
    }

    public function negotiationProvider()
    {
        return array(
            array('text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8', 'text/html; charset=UTF-8'),
            array('application/xml,application/xhtml+xml,text/html;q=0.9, text/plain;q=0.8,image/png,*/*;q=0.5', 'text/html; charset=UTF-8'),
            array('image/jpeg, application/x-ms-application, image/gif, application/xaml+xml, image/pjpeg, application/x-ms-xbap, application/x-shockwave-flash, application/msword, */*', 'text/html; charset=UTF-8'),
            array('application/json, */*', 'application/json')
        );
    }

    private function request($accept)
    {
        $app = new Application('test');
        $app['dispatcher']->addSubscriber(new ContentNegotiationSubscriber($app));
        $app->get('/content/negociation', function () {
            return '';
        });
        $client = new Client($app);
        $client->request('GET', '/content/negociation',
            array(),
            array(),
            array(
                'HTTP_Accept' => $accept
            )
        );

        return $client->getResponse();
    }
}
