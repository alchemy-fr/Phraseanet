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

use Alchemy\Phrasea\Media\IntegerTechnicalData;
use Alchemy\Phrasea\Media\TechnicalData;

final class IntegerTechnicalDataTest extends \PHPUnit\Framework\TestCase
{
    public function testItImplementsTechnicalData()
    {
        $this->assertInstanceOf(TechnicalData::class, new IntegerTechnicalData('foo', 314159));
    }

    public function testItReturnsItsName()
    {
        $sut = new IntegerTechnicalData('foo', 314159);

        $this->assertSame('foo', $sut->getName());
    }

    public function testItReturnsItsValue()
    {
        $sut = new IntegerTechnicalData('foo', '314159');

        $this->assertSame(314159, $sut->getValue());
    }
}
