<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Tests\Phrasea\Metadata;

use Alchemy\Phrasea\Metadata\Tag;
use Alchemy\Phrasea\Metadata\TagInterface;
use PHPExiftool\Driver\TagGroupInterface;
use PHPUnit\Framework\Attributes\DataProvider;

class PhraseanetTagsTest extends \PHPUnit\Framework\TestCase
{
    #[DataProvider('provideSUTs')]
    public function testItImplementsTagInterface($sut)
    {
        $this->assertInstanceOf(TagInterface::class, $sut);
        $this->assertInstanceOf(TagGroupInterface::class, $sut);
    }

    #[DataProvider('provideSUTs')]
    public function testItsGetMethodsReturnStrings($sut)
    {
        $this->assertIsString($sut->getDescription());
        $this->assertSame('Phraseanet', $sut->getGroupName());
        $this->assertStringStartsWith('Phraseanet:', $sut->getId());
        $this->assertIsString($sut->getName());
        $this->assertSame($sut->getId(), $sut->getTagname());
        $this->assertSame('Phraseanet:' . $sut->getName(), $sut->getTagname());
        $this->assertFalse($sut->isWritable());
    }

    public static function provideSUTs()
    {
        return [
            [new Tag\NoSource()],
            [new Tag\PdfText()],
            [new Tag\TfArchivedate()],
            [new Tag\TfAtime()],
            [new Tag\TfBasename()],
            [new Tag\TfBits()],
            [new Tag\TfChannels()],
            [new Tag\TfCtime()],
            [new Tag\TfDirname()],
            [new Tag\TfDuration()],
            [new Tag\TfEditdate()],
            [new Tag\TfExtension()],
            [new Tag\TfFilename()],
            [new Tag\TfFilepath()],
            [new Tag\TfHeight()],
            [new Tag\TfMimetype()],
            [new Tag\TfMtime()],
            [new Tag\TfQuarantine()],
            [new Tag\TfRecordid()],
            [new Tag\TfSize()],
            [new Tag\TfWidth()],
        ];
    }

    #[DataProvider('providesNoSourceTags')]
    public function testItRegistersFieldNameOnCreation($expected, Tag\NoSource $sut)
    {
        $this->assertSame($expected, $sut->getFieldName());
    }

    public static function providesNoSourceTags()
    {
        return [
            ['', new Tag\NoSource()],
            ['foo', new Tag\NoSource('foo')],
        ];
    }
}
