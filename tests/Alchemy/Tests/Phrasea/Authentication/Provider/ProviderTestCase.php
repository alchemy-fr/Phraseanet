<?php

namespace Alchemy\Tests\Phrasea\Authentication\Provider;

use Alchemy\Phrasea\Authentication\ACLProvider;
use Alchemy\Phrasea\Authentication\Exception\NotAuthenticatedException;
use Alchemy\Phrasea\Authentication\Provider\Factory as ProviderFactory;
use Alchemy\Phrasea\Authentication\Provider\ProviderInterface;
use Alchemy\Phrasea\Model\Manipulator\UserManipulator;
use Alchemy\Phrasea\Model\Repositories\UserRepository;
use Alchemy\Phrasea\Model\Repositories\UsrAuthProviderRepository;
use appbox;
use DataURI\Parser;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use RandomLib\Generator as RandomGenerator;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;


abstract class ProviderTestCase extends \PhraseanetTestCase
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
        $this->assertIsString($this->getProvider()->getId());
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
        $this->assertInstanceOf(ClientInterface::class, $provider->getGuzzleClient());
        $guzzle = $this->getGuzzleMock();

        $provider->setGuzzleClient($guzzle);
        $this->assertEquals($guzzle, $provider->getGuzzleClient());
    }

    public function testGetTemplates()
    {
        $provider = $this->getProvider();

        $identity = $this->createStub('Alchemy\Phrasea\Authentication\Provider\Token\Identity');

        $this->assertEquals([], $provider->getTemplates($identity));
    }

    public function testGetName()
    {
        $this->assertIsString($this->getProvider()->getName());
    }

    public function testIsBuiltWithFactory()
    {
        $provider = $this->getProvider();

        $built = $this->getProviderFactory()->build(
            $provider->getId(),
            $provider->getType(),
            true,
            $provider->getId(),
            $this->getTestOptions()
        );

        $this->assertInstanceOf(get_class($provider), $built);
    }

    public function testAuthenticate()
    {
        $provider = $this->getProviderForAuthentication();
        $redirect = $provider->authenticate();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $redirect);
    }

    public function testOnCallbackWithSuccess()
    {
        $cases = $this->provideDataForSuccessCallback();
        $this->assertNotEmpty($cases);

        foreach ($cases as list($provider, $request)) {
            $provider->onCallback($request);
            $this->addToAssertionCount(1);
        }
    }

    /**
     * Cases are built by a non-static method because they need mocks, which PHPUnit data providers cannot create.
     */
    public function testOnCallbackWithFailure()
    {
        $cases = $this->provideDataForFailingCallback();
        $this->assertNotEmpty($cases);

        foreach ($cases as $i => list($provider, $request)) {
            try {
                $provider->onCallback($request);
                $this->fail(sprintf('Case #%s should throw a NotAuthenticatedException', $i));
            } catch (NotAuthenticatedException $e) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testGetToken()
    {
        $provider = $this->getProvider();
        $this->authenticateProvider($provider);

        $token = $provider->getToken();

        $this->assertInstanceOf('Alchemy\Phrasea\Authentication\Provider\Token\Token', $token);
        $this->assertEquals($provider, $token->getProvider());
    }

    public function testGetTokenWhenNotAuthenticated()
    {
        $this->expectException(\Alchemy\Phrasea\Authentication\Exception\NotAuthenticatedException::class);
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

    public function testGetIdentityWhenNotAuthenticated()
    {
        $this->expectException(\Alchemy\Phrasea\Authentication\Exception\NotAuthenticatedException::class);
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

    /**
     * @return array list of [ProviderInterface $provider, Request $request]
     */
    abstract public function provideDataForFailingCallback();

    /**
     * @return array list of [ProviderInterface $provider, Request $request]
     */
    abstract public function provideDataForSuccessCallback();

    protected function addQueryParameter(Request $request, array $parameters)
    {
        $request->query = new InputBag($parameters);
    }

    protected function getMockSession()
    {
        return new Session(new MockFileSessionStorage());
    }

    public function testLogout()
    {
        $this->getProviderForLogout()->logout();
    }

    abstract protected function authenticateProvider(ProviderInterface $provider);

    /**
     * @return ProviderInterface
     */
    abstract protected function getProviderForAuthentication();

    /**
     * @return ProviderInterface
     */
    abstract protected function getProviderForLogout();

    /**
     * @return ProviderInterface
     */
    abstract protected function getProviderForSuccessIdentity();

    /**
     * @return ProviderInterface
     */
    abstract protected function getProviderForFailingIdentity();

    /**
     * @return ProviderInterface
     */
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
        return $this->createMock('Symfony\Component\Routing\Generator\UrlGenerator');
    }

    protected function getRequestMock()
    {
        return new Request();
    }

    /**
     * Returns a client answering every request with the same response.
     *
     * @param int $statusCode
     * @param string $body
     *
     * @return Client
     */
    protected function getGuzzleMock($statusCode = 200, $body = '')
    {
        $handler = function (RequestInterface $request, array $options) use ($statusCode, $body) {
            return Create::promiseFor(new Response($statusCode, [], $body));
        };

        return new Client(['handler' => HandlerStack::create($handler)]);
    }

    /**
     * Returns a client answering the successive requests with the given responses.
     *
     * @param Response[] $responses
     *
     * @return Client
     */
    protected function getGuzzleMockWithResponses(array $responses)
    {
        return new Client(['handler' => HandlerStack::create(new MockHandler($responses))]);
    }

    /**
     * @return ProviderFactory
     */
    private function getProviderFactory()
    {
        return new ProviderFactory(
            $this->getUrlGeneratorMock(),
            $this->getMockSession(),
            $this->createStub(UserManipulator::class),
            $this->createStub(UserRepository::class),
            $this->createStub(ACLProvider::class),
            $this->createStub(appbox::class),
            $this->createStub(RandomGenerator::class),
            $this->createStub(UsrAuthProviderRepository::class),
            $this->createStub(EntityManager::class)
        );
    }
}
