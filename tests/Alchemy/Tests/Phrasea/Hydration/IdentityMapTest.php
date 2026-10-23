<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Tests\Phrasea\Hydration;

use Alchemy\Phrasea\Hydration\Hydrator;
use Alchemy\Phrasea\Hydration\IdentityMap;
use Prophecy\Argument;

class IdentityMapTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var IdentityMap
     */
    private $sut;

    protected function setUp()
    {
        $hydrator = $this->prophesize(Hydrator::class);

        $hydrator->extract(Argument::type(\stdClass::class))
            ->will(function ($args) {
                return (array)$args[0];
            });

        $hydrator->hydrate(Argument::type(\stdClass::class), Argument::type('array'))
            ->will(function ($args) {

                foreach ($args[1] as $property => $value) {
                    $args[0]->{$property} = $value;
                }
            });

        $this->sut = new IdentityMap($hydrator->reveal(), (object)['foo' => null, 'bar' => null]);
    }

    public function testItShouldBeArrayAccessibleTraversableAndCountable()
    {
        $this->assertInstanceOf(\Traversable::class, $this->sut);
        $this->assertInstanceOf(\ArrayAccess::class, $this->sut);
        $this->assertInstanceOf(\Countable::class, $this->sut);
    }

    public function testItShouldHydrateAnInstanceWhenNotYetInMap()
    {
        $expected = (object)['foo' => 'foo', 'bar' => 'bar'];

        $instance = $this->sut->hydrate(42, ['foo' => 'foo', 'bar' => 'bar']);

        $this->assertEquals($expected, $instance, 'Invalid instance generated');

        $this->assertSame($instance, $this->sut[42], 'Accessing by offset should succeed');
    }

    public function testItShouldReHydrateAnInstance()
    {
        $instance  = $this->sut->hydrate(42, ['foo' => 'Foo', 'bar' => 'Bar']);

        $this->assertAttributeSame('Foo', 'foo', $instance);
        $this->assertAttributeSame('Bar', 'bar', $instance);

        $instance2 = $this->sut->hydrate(42, ['foo' => 'new foo value', 'bar' => null]);

        $this->assertAttributeSame('new foo value', 'foo', $instance);
        $this->assertAttributeSame(null, 'bar', $instance);

        $this->assertSame($instance, $instance2, 'Same instance was not rehydrated');

        $this->assertCount(1, $this->sut);
    }

    public function testItsOffsetCanBeUnset()
    {
        $this->sut[42] = ['foo' => 'Foo', 'bar' => 'Bar'];

        $this->assertTrue(isset($this->sut[42]), 'Offset should exists');

        unset($this->sut[42]);

        $this->assertFalse(isset($this->sut[42]), 'Offset should not exists after unset');
    }

    public function testItHydratesAllEntities()
    {
        $data = [
            ['foo' => 'Foo1', 'bar' => 'Bar1'],
            ['foo' => 'Foo2', 'bar' => 'Bar2'],
        ];

        $this->sut->hydrateAll($data);

        $entities = [];

        foreach ($this->sut as $key => $value) {
            $entities[$key] = $value;
        }

        foreach ($data as $key => $value) {
            $this->assertArrayHasKey($key, $entities, 'An entity is missing');
            $this->assertEquals((object)$value, $entities[$key], 'Unexpected entity value');
        }

        $this->assertCount(2, $this->sut);
        $this->sut->clear();
        $this->assertCount(0, $this->sut, 'Map was not cleared');
    }
}
