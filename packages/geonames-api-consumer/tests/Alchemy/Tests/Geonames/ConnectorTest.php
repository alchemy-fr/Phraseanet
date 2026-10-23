<?php

namespace Alchemy\Tests\Geonames;

use Alchemy\Geonames\Connector;
use Alchemy\Geonames\Geoname;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

class ConnectorTest extends \PHPUnit\Framework\TestCase
{
    /** @var array */
    private $history = array();

    public function testSearchShouldReturnResults()
    {
        $guzzle = $this->getGuzzleMock(new Response(200, array(), file_get_contents(__DIR__ . '/Fixtures/search.json')));

        $connector = new Connector($guzzle, 'http://geoloc.com');
        $geonames = $connector->search('orleans');

        $this->assertCount(2, $geonames);
        $this->assertEquals(2988507, $geonames[0]->get('geonameid'));
        $this->assertEquals(4717560, $geonames[1]->get('geonameid'));
    }

    public function testSearchShouldQueryWithRightOptions()
    {
        $guzzle = $this->getGuzzleMock(new Response(200, array(), '[]'));

        $connector = new Connector($guzzle, 'http://geoloc.com');
        $this->assertEquals(array(), $connector->search('orleans', 42, '66.6.66.6'));

        $request = $this->getLastRequest();
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('http://geoloc.com/city', (string) $request->getUri()->withQuery(''));
        $this->assertSame('application/json', $request->getHeaderLine('Accept'));
        $this->assertSame(array(
            'sort'      => 'closeness',
            'name'      => 'orleans',
            'limit'     => '42',
            'client-ip' => '66.6.66.6',
        ), $this->getQuery($request));
    }

    public function testGeonameIdShouldReturnAGeoname()
    {
        $guzzle = $this->getGuzzleMock(new Response(200, array(), file_get_contents(__DIR__ . '/Fixtures/geoname.json')));

        $connector = new Connector($guzzle, 'http://geoloc.com');
        $geoname = $connector->geoname(2992092);

        $this->assertInstanceOf('Alchemy\Geonames\Geoname', $geoname);
        $this->assertEquals(2992092, $geoname->get('geonameid'));
    }

    public function testGeonameIdShouldReturnAGeonameWithRightOptions()
    {
        $guzzle = $this->getGuzzleMock(new Response(200, array(), '{"geonameid":"2992092"}'));

        $connector = new Connector($guzzle, 'http://geoloc.com/');
        $this->assertEquals(new Geoname(array('geonameid' => 2992092)), $connector->geoname(2992092));

        $request = $this->getLastRequest();
        $this->assertSame('http://geoloc.com/city/2992092', (string) $request->getUri());
        $this->assertSame('application/json', $request->getHeaderLine('Accept'));
    }

    public function testIPShouldReturnAGeoname()
    {
        $guzzle = $this->getGuzzleMock(new Response(200, array(), file_get_contents(__DIR__ . '/Fixtures/ip.json')));

        $connector = new Connector($guzzle, 'http://geoloc.com');
        $geoname = $connector->ip('66.6.6.6');

        $this->assertInstanceOf('Alchemy\Geonames\Geoname', $geoname);
        $this->assertEquals(2992090, $geoname->get('geonameid'));
    }

    public function testIPShouldReturnAGeonameWithOptions()
    {
        $guzzle = $this->getGuzzleMock(new Response(200, array(), '{"geonameid":2992090}'));

        $connector = new Connector($guzzle, 'http://geoloc.com/');
        $this->assertEquals(new Geoname(array('geonameid' => 2992090)), $connector->ip('66.6.6.6'));

        $request = $this->getLastRequest();
        $this->assertSame('http://geoloc.com/ip', (string) $request->getUri()->withQuery(''));
        $this->assertSame(array('ip' => '66.6.6.6'), $this->getQuery($request));
    }

    public function testIPWithInvalidResponse()
    {
        $guzzle = $this->getGuzzleMock(new Response(200, array(), 'invalidJson'));

        $connector = new Connector($guzzle, 'http://geoloc.com/');

        $this->expectException(\Alchemy\Geonames\Exception\TransportException::class);
        $this->expectExceptionMessage('Unable to parse result');
        $connector->ip('66.6.6.6');
    }

    public function testIPWithGuzzleClientException()
    {
        $guzzle = $this->getGuzzleMock(new Response(404, array(), 'Invalid resource'));

        $connector = new Connector($guzzle, 'http://geoloc.com/');

        $this->expectException(\Alchemy\Geonames\Exception\NotFoundException::class);
        $connector->ip('66.6.6.6');
    }

    public function testIPWithGuzzleClientExceptionForbidden()
    {
        $guzzle = $this->getGuzzleMock(new Response(403, array(), 'Invalid resource'));

        $connector = new Connector($guzzle, 'http://geoloc.com/');

        $this->expectException(\Alchemy\Geonames\Exception\TransportException::class);
        $this->expectExceptionMessage('Failed to execute query');
        $connector->ip('66.6.6.6');
    }

    public function testIPWithGuzzleServerException()
    {
        $guzzle = $this->getGuzzleMock(new Response(500, array(), 'Invalid resource'));

        $connector = new Connector($guzzle, 'http://geoloc.com/');

        $this->expectException(\Alchemy\Geonames\Exception\TransportException::class);
        $this->expectExceptionMessage('Failed to execute query');
        $connector->ip('66.6.6.6');
    }

    public function testIPWithGuzzleConnectException()
    {
        $guzzle = $this->getGuzzleMock(new ConnectException('Connection refused', new Request('GET', 'http://geoloc.com/ip')));

        $connector = new Connector($guzzle, 'http://geoloc.com/');

        $this->expectException(\Alchemy\Geonames\Exception\TransportException::class);
        $connector->ip('66.6.6.6');
    }

    public function testCreate()
    {
        $this->assertInstanceOf('Alchemy\Geonames\Connector', Connector::create('http://geoloc.com'));
    }

    private function getGuzzleMock($response)
    {
        $this->history = array();

        $stack = HandlerStack::create(new MockHandler(array($response)));
        $stack->push(Middleware::history($this->history));

        return new Client(array('handler' => $stack));
    }

    /**
     * @return RequestInterface
     */
    private function getLastRequest()
    {
        $this->assertCount(1, $this->history);

        return $this->history[0]['request'];
    }

    private function getQuery(RequestInterface $request)
    {
        parse_str($request->getUri()->getQuery(), $query);

        return $query;
    }
}
