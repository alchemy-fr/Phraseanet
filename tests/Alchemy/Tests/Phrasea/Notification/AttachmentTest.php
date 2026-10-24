<?php

namespace Alchemy\Tests\Phrasea\Notification;

use Alchemy\Phrasea\Notification\Attachment;

class AttachmentTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Attachment
     */
    protected $object;
    protected $tempFile;

    protected function setUp(): void
    {
        $this->tempFile = tempnam(null, 'attachment');
        $this->object = new Attachment($this->tempFile, 'test.txt', 'text/plain');
    }

    public function testAsSwiftAttachment()
    {
        /** @var $a \Swift_Mime_Attachment */
        $swa = $this->object->As_Swift_Attachment();

        $this->assertInstanceOf('Swift_Attachment', $swa);
        $this->assertEquals($swa->getContentType(), 'text/plain');
        $this->assertEquals($swa->getFilename(), 'test.txt');

        unset($swa);
    }

    public function tearDown(): void
    {
        unlink($this->tempFile);
    }
}
