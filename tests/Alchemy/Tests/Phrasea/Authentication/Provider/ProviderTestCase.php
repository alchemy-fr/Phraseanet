<?php

namespace Alchemy\Tests\Phrasea\Authentication\Provider;

use Alchemy\Phrasea\Authentication\Provider\ProviderInterface;
use DataURI\Parser;
use Alchemy\Phrasea\Authentication\Provider\Token\Identity;
use Alchemy\Phrasea\Authentication\Provider\Factory as ProviderFactory;
use Guzzle\Http\Message\RequestInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;
use Symfony\Component\HttpFoundation\Session\Session;

abstract class ProviderTestCase extends \PHPUnit_Framework_TestCase
{
    protected $session;

    const COMPANY = 'Company test';
    const EMAIL = 'email@test.com';
    const FIRSTNAME = 'first-name';
    const USERNAME = 'user-name';
    const LASTNAME = 'last-name';
    const ID = '1234567890';
    const IMAGEURL = 'https://www.home.org/image.png';

    public function testGetId()
    {
        $this->assertInternalType('string', $this->getProvider()->getId());
    }

    public function testGetSetUrlGenerator()
    {
        $provider = $this->getProvider();
        $this->assertInstanceOf('Symfony\Component\Routing\Generator\UrlGenerator', $provider->getUrlGenerator());
        $generator = $this->getUrlGeneratorMock();

        $provider->setUrlGenerator($generator);
        $this->assertEquals($generator, $provider->getUrlGenerator());
    }

    public function testGetSetSession()
    {
        $provider = $this->getProvider();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Session\SessionInterface', $provider->getSession());
        $session = $this->getMockSession();

        $provider->setSession($session);
        $this->assertEquals($session, $provider->getSession());
    }

    public function testGetSetGuzzleClient()
    {
        $provider = $this->getProvider();
        $this->assertInstanceOf('Guzzle\Http\CLientInterface', $provider->getGuzzleClient());
        $guzzle = $this->getGuzzleMock();

        $provider->setGuzzleClient($guzzle);
        $this->assertEquals($guzzle, $provider->getGuzzleClient());
    }

    public function testGetName()
    {
        $this->assertInternalType('string', $this->getProvider()->getName());
    }

    public function testIsBuiltWithFactory()
    {
        $provider = $this->getProvider();

        $built = $this->getProviderFactory()->build($provider->getId(), $this->getTestOptions());

        $this->assertInstanceOf(get_class($provider), $built);
    }

    public function testAuthenticate()
    {
        $provider = $this->getProviderForAuthentication();
        $redirect = $provider->authenticate();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $redirect);
    }

    /**
     * @dataProvider provideDataForSuccessCallback
     */
    public function testOnCallbackWithSuccess(ProviderInterface $provider, $request)
    {
        $provider->onCallback($request);
    }

    /**
     * @dataProvider provideDataForFailingCallback
     * @expectedException Alchemy\Phrasea\Exception\RuntimeException
     */
    public function testOnCallbackWithFailure($provider, $request)
    {
        $provider->onCallback($request);
    }

    public function testGetToken()
    {
        $provider = $this->getProvider();
        $this->authenticate($provider);

        $token = $provider->getToken();

        $this->assertInstanceOf('Alchemy\Phrasea\Authentication\Provider\Token\Token', $token);
        $this->assertEquals($provider, $token->getProvider());
    }

    /**
     * @expectedException Alchemy\Phrasea\Exception\RuntimeException
     */
    public function testGetTokenWhenNotAuthenticated()
    {
        $this->getProvider()->getToken();
    }

    public function testGetIdentity()
    {
        $provider = $this->getProviderForSuccessIdentity();
        $identity = $provider->getIdentity();

        $this->assertInstanceOf('Alchemy\Phrasea\Authentication\Provider\Token\Identity', $identity);

        foreach ($this->getAvailableFieldsForIdentity() as $name=>$value) {
            $this->assertEquals($value, $identity->get($name), "testing $name");
        }
    }

    /**
     * @expectedException Alchemy\Phrasea\Exception\RuntimeException
     */
    public function testGetIdentityWhenNotAuthenticated()
    {
        $provider = $this->getProviderForFailingIdentity();
        $provider->getIdentity();
    }

    public function testGetIconURI()
    {
        Parser::parse($this->getProvider()->getIconURI());
    }

    public function testCreate()
    {
        $name = get_class($this->getProvider());
        $provider = $name::create($this->getUrlGeneratorMock(), $this->getMockSession(), $this->getTestOptions());

        $this->assertInstanceOf($name, $provider);
    }

    abstract public function provideDataForFailingCallback();

    abstract public function provideDataForSuccessCallback();

    protected function addQueryParameter(Request $request, array $parameters)
    {
        $query = $this->getMockBuilder('Symfony\Component\HttpFoundation\ParameterBag')
            ->disableOriginalConstructor()
            ->getMock();

        $query->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(function ($param) use ($parameters) {
                if (isset($parameters[$param])) {
                    return $parameters[$param];
                }
            }));

        $request->query = $query;
    }

    protected function getMockSession()
    {
        return new Session(new MockFileSessionStorage());
    }

    abstract protected function authenticate(ProviderInterface $provider);

    abstract protected function getProviderForAuthentication();

    abstract protected function getProviderForSuccessIdentity();

    abstract protected function getProviderForFailingIdentity();

    abstract protected function getAvailableFieldsForIdentity();

    /**
     * @return ProviderInterface
     */
    abstract protected function getProvider();

    /**
     * @return array
     */
    abstract protected function getTestOptions();

    protected function getUrlGeneratorMock()
    {
        return $this->getMockBuilder('Symfony\Component\Routing\Generator\UrlGenerator')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function getRequestMock()
    {
        return $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function getGuzzleMock($statusCode = 200)
    {
        $mock = $this->getMock('Guzzle\Http\ClientInterface');

        $requestGet = $this->getMock('Guzzle\Http\Message\RequestInterface');
        $requestPost = $this->getMock('Guzzle\Http\Message\RequestInterface');

        $queryString = $this->getMockBuilder('Guzzle\Http\QueryString')
            ->disableOriginalConstructor()
            ->getMock();

        $requestGet->expects($this->any())
            ->method('getQuery')
            ->will($this->returnValue($queryString));

        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $response->expects($this->any())
            ->method('getStatusCode')
            ->will($this->returnValue($statusCode));

        $requestGet->expects($this->any())
            ->method('send')
            ->will($this->returnValue($response));

        $requestPost->expects($this->any())
            ->method('send')
            ->will($this->returnValue($response));

        $mock->expects($this->any())
            ->method('get')
            ->will($this->returnValue($requestGet));

        $mock->expects($this->any())
            ->method('post')
            ->will($this->returnValue($requestPost));

        return $mock;
    }

    /**
     * @return ProviderFactory
     */
    private function getProviderFactory()
    {
        return new ProviderFactory($this->getUrlGeneratorMock(), $this->getMockSession());
    }
}
