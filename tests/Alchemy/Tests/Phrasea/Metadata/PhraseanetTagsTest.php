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

class PhraseanetTagsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider provideSUTs
     */
    public function testItImplementsTagInterface($sut)
    {
        $this->assertInstanceOf('PHPExiftool\\Driver\\TagInterface', $sut);
    }

    /**
     * @dataProvider provideSUTs
     */
    public function testItsGetMethodsReturnStrings($sut)
    {
        $this->assertInternalType('string', $sut->getDescription());
        $this->assertSame('Phraseanet', $sut->getGroupName());
        $this->assertInternalType('string', $sut->getId());
        $this->assertInternalType('string', $sut->getName());
        $this->assertStringStartsWith('Phraseanet:', $sut->getTagname());
    }

    public function provideSUTs()
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

    /**
     * @dataProvider providesNoSourceTags
     */
    public function testItRegistersFieldNameOnCreation($expected, Tag\NoSource $sut)
    {
        $this->assertSame($expected, $sut->getFieldName());
    }

    public function providesNoSourceTags()
    {
        return [
            ['', new Tag\NoSource()],
            ['foo', new Tag\NoSource('foo')],
        ];
    }
}
