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

use Alchemy\Phrasea\Media\StringTechnicalData;
use Alchemy\Phrasea\Media\TechnicalData;

final class StringTechnicalDataTest extends \PHPUnit\Framework\TestCase
{
    public function testItImplementsTechnicalData()
    {
        $this->assertInstanceOf(TechnicalData::class, new StringTechnicalData('foo', 'bar'));
    }

    public function testItReturnsItsName()
    {
        $sut = new StringTechnicalData('foo', '314159');

        $this->assertSame('foo', $sut->getName());
    }

    public function testItReturnsItsValue()
    {
        $sut = new StringTechnicalData('foo', 314159);

        $this->assertSame('314159', $sut->getValue());
    }
}
