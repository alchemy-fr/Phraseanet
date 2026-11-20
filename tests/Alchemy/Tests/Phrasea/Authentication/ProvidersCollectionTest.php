<?php

namespace Alchemy\Tests\Phrasea\Authentication;

use Alchemy\Phrasea\Authentication\ProvidersCollection;
use Alchemy\Phrasea\Exception\InvalidArgumentException;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class ProvidersCollectionTest extends \PhraseanetTestCase
{
    public function testGetIterator()
    {
        $provider = $this->getProviderMock('neutron-provider');

        $providers = new ProvidersCollection();
        $providers->register($provider);

        $expectedIterator = new \ArrayIterator(['neutron-provider' => $provider]);

        $this->assertEquals($expectedIterator, $providers->getIterator());
    }

    public function testRegister()
    {
        $provider = $this->getProviderMock('neutron-provider');

        $providers = new ProvidersCollection();
        $providers->register($provider);
    }

    public function testHas()
    {
        $provider = $this->getProviderMock('neutron-provider');
        $providers = new ProvidersCollection();

        $this->assertFalse($providers->has('neutron-provider'));

        $providers->register($provider);

        $this->assertTrue($providers->has('neutron-provider'));
    }

    public function testGet()
    {
        $provider = $this->getProviderMock('neutron-provider');
        $providers = new ProvidersCollection();

        $providers->register($provider);

        $this->assertSame($provider, $providers->get('neutron-provider'));
    }

    public function testGetOnNonExistentFails()
    {
        $provider = $this->getProviderMock('neutron-provider');
        $providers = new ProvidersCollection();

        try {
            $providers->get('neutron-provider');
            $this->fail('Should have raised an exception');
        } catch (InvalidArgumentException $e) {

        }
    }

    public function testCount()
    {
        $provider = $this->getProviderMock('neutron-provider');
        $providers = new ProvidersCollection();

        $this->assertEquals(0, count($providers));
        $providers->register($provider);
        $this->assertEquals(1, count($providers));
    }

    private function getProviderMock($id)
    {
        $provider = $this->createMock('Alchemy\Phrasea\Authentication\Provider\ProviderInterface');
        $provider
            ->method('getId')
            ->will($this->returnValue($id));

        return $provider;
    }
}
