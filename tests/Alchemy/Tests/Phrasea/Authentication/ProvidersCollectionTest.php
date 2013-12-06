<?php

namespace Alchemy\Tests\Phrasea\Authentication;

use Alchemy\Phrasea\Authentication\ProvidersCollection;
use Alchemy\Phrasea\Exception\InvalidArgumentException;

class ProvidersCollectionTest extends \PhraseanetTestCase
{
    /**
     * @covers Alchemy\Phrasea\Authentication\ProvidersCollection::getIterator
     */
    public function testGetIterator()
    {
        $provider = $this->getProviderMock('neutron-provider');

        $providers = new ProvidersCollection();
        $providers->register($provider);

        $expectedIterator = new \ArrayIterator(['neutron-provider' => $provider]);

        $this->assertEquals($expectedIterator, $providers->getIterator());
    }

    /**
     * @covers Alchemy\Phrasea\Authentication\ProvidersCollection::register
     */
    public function testRegister()
    {
        $provider = $this->getProviderMock('neutron-provider');

        $providers = new ProvidersCollection();
        $providers->register($provider);
    }

    /**
     * @covers Alchemy\Phrasea\Authentication\ProvidersCollection::has
     */
    public function testHas()
    {
        $provider = $this->getProviderMock('neutron-provider');
        $providers = new ProvidersCollection();

        $this->assertFalse($providers->has('neutron-provider'));

        $providers->register($provider);

        $this->assertTrue($providers->has('neutron-provider'));
    }

    /**
     * @covers Alchemy\Phrasea\Authentication\ProvidersCollection::get
     */
    public function testGet()
    {
        $provider = $this->getProviderMock('neutron-provider');
        $providers = new ProvidersCollection();

        $providers->register($provider);

        $this->assertSame($provider, $providers->get('neutron-provider'));
    }

    /**
     * @covers Alchemy\Phrasea\Authentication\ProvidersCollection::get
     */
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

    /**
     * @covers Alchemy\Phrasea\Authentication\ProvidersCollection::count
     */
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
        $provider = $this->getMock('Alchemy\Phrasea\Authentication\Provider\ProviderInterface');
        $provider->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id));

        return $provider;
    }
}
