<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Tests\Phrasea\Media;

use Alchemy\Phrasea\Media\ArrayTechnicalDataSet;
use Alchemy\Phrasea\Media\TechnicalData;
use Alchemy\Phrasea\Media\TechnicalDataSet;
use ArrayIterator;
use Assert\AssertionFailedException;

class ArrayTechnicalDataSetTest extends \PHPUnit\Framework\TestCase
{
    /** @var ArrayTechnicalDataSet */
    private $sut;

    protected function setUp()
    {
        $this->sut = new ArrayTechnicalDataSet();
    }

    public function testItIsInstantiable()
    {
        $this->assertInstanceOf(ArrayTechnicalDataSet::class, $this->sut);
    }

    public function testItImplementsTechnicalDataSet()
    {
        $this->assertInstanceOf(TechnicalDataSet::class, $this->sut);
    }

    public function testItDefaultsToAnEmptySet()
    {
        $this->assertTrue($this->sut->isEmpty());
    }

    public function testItCanBeInitializedWithASet()
    {
        $data = $this->createTechnicalData('foo', 'bar');

        $sut = new ArrayTechnicalDataSet([$data]);

        $this->assertFalse($sut->isEmpty(), 'Sut should not be an empty set.');
        $this->assertSame($data, $sut['foo']);
    }

    public function testItShouldThrowExceptionWhenTryingBadNameSet()
    {
        $data = $this->createTechnicalData('foo', 'bar');

        $this->setExpectedException(AssertionFailedException::class);

        $this->sut->offsetSet('bar', $data);
    }

    public function testItShouldSetDataByName()
    {
        $data = $this->createTechnicalData('foo', 'bar');

        $this->sut->offsetSet('foo', $data);

        $this->assertSame($data, $this->sut->offsetGet('foo'));
    }

    public function testItShouldNotThrowExceptionWhenAdding()
    {
        $data = $this->createTechnicalData('foo', 'bar');

        $this->sut[] = $data;

        $this->assertSame($data, $this->sut['foo']);
    }

    public function testItLooksForASetData()
    {
        $data = $this->createTechnicalData('foo', 'bar');

        $this->assertFalse($this->sut->offsetExists($data));

        $this->sut[] = $data;

        $this->assertTrue($this->sut->offsetExists('foo'));
        $this->assertTrue($this->sut->offsetExists($data));
    }

    public function testItCountDataInSet()
    {
        $this->assertCount(0, $this->sut);

        $this->sut[] = $this->createTechnicalData('foo', 'bar');
        $this->sut[] = $this->createTechnicalData('bar', 'baz');

        $this->assertCount(2, $this->sut);

        $this->sut[] = $this->createTechnicalData('foo', 'bar');

        $this->assertCount(2, $this->sut);
    }

    public function testItGetsValues()
    {
        $this->sut[] = $this->createTechnicalData('foo', 'bar');
        $this->sut[] = $this->createTechnicalData('bar', 'baz');

        $this->assertEquals(['foo' => 'bar', 'bar' => 'baz'], $this->sut->getValues());
    }

    public function testItIsAnArrayIterator()
    {
        $this->sut[] = $fooData = $this->createTechnicalData('foo', 'bar');
        $this->sut[] = $barData = $this->createTechnicalData('bar', 'baz');

        $iterator = $this->sut->getIterator();
        $this->assertInstanceOf(ArrayIterator::class, $iterator);

        $this->assertEquals(['foo' => $fooData, 'bar' => $barData], $iterator->getArrayCopy());
    }

    public function testItCanUnsetAKey()
    {
        $this->sut[] = $fooData = $this->createTechnicalData('foo', 'bar');
        $this->sut[] = $barData = $this->createTechnicalData('bar', 'baz');

        unset($this->sut['foo']);
        $this->sut->offsetUnset($barData);

        $this->assertTrue($this->sut->isEmpty());
    }

    /**
     * @return TechnicalData
     */
    private function createTechnicalData($name, $value)
    {
        $data = $this->prophesize(TechnicalData::class);
        $data->getName()->willReturn($name);
        $data->getValue()->willReturn($value);

        return $data->reveal();
    }
}
