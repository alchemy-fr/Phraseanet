<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Tests\Phrasea\Filesystem;

use Alchemy\Phrasea\Filesystem\FilesystemService;
use Alchemy\Phrasea\Filesystem\PhraseanetFilesystem as Filesystem;
use Alchemy\Phrasea\Model\RecordInterface;

// use Symfony\Component\Filesystem\Filesystem;


class FilesystemServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FilesystemService
     */
    private $sut;

    protected function setUp()
    {
        $this->sut = new FilesystemService(new Filesystem());
    }

    /**
     * @dataProvider provideRecordsAndExpectedDocumentFilenames
     */
    public function testItProperlyGeneratesDocumentFileNames($expected, $recordId, $filename)
    {
        $record = $this->prophesize(RecordInterface::class);
        $record->getRecordId()->willReturn($recordId);

        $this->assertEquals($expected, $this->sut->generateDocumentFilename($record->reveal(), $filename));
    }

    public function provideRecordsAndExpectedDocumentFileNames()
    {
        return [
            ['2_document.jpg', 2, 'foo.jpg'],
            ['42_document.jpg', 42, 'bar.JPG'],
            ['2_document.pdf', 2, new \SplFileInfo('foo_bar.pdf')],
        ];
    }
}
