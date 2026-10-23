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
use Alchemy\Phrasea\Hydration\ReflectionHydrator;

class ReflectionHydratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ReflectionHydrator
     */
    private $sut;

    protected function setUp()
    {
        $this->sut = new ReflectionHydrator(ToHydrate::class, ['foo', 'bar']);
    }

    public function testItThrowsExceptionOnUnknownProperty()
    {
        $this->setExpectedException(\ReflectionException::class);

        $sut = new ReflectionHydrator(ToHydrate::class, ['baz']);
        $sut->extract(new ToHydrate());
    }

    public function testItShouldImplementHydrator()
    {
        $this->assertInstanceOf(Hydrator::class, $this->sut);
    }

    public function testItShouldProperlyHydrateInstance()
    {
        $stub = new ToHydrate();

        $this->sut->hydrate($stub, ['foo' => 'foo modified', 'bar' => 'bar changed']);

        $this->assertEquals('foo modified', $stub->getFoo(), 'Property foo was not hydrated');
        $this->assertEquals('bar changed', $stub->getBar(), 'Property bar was not hydrated');
    }

    public function testItShouldProperlyExtractData()
    {
        $data = $this->sut->extract(new ToHydrate());

        $this->assertSame(['foo' => 'foo', 'bar' => 'bar'], $data, 'Improper extraction of properties');
    }
}

class ToHydrate
{
    private $foo = 'foo';
    private $bar = 'bar';

    public function getFoo()
    {
        return $this->foo;
    }

    public function getBar()
    {
        return $this->bar;
    }
}
