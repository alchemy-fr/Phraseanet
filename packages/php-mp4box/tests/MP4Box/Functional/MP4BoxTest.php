<?php

namespace MP4Box\Functional;

use Symfony\Component\Process\ExecutableFinder;
use PHPUnit\Framework\TestCase;
use MP4Box\Exception\RuntimeException;
use MP4Box\MP4Box;

class MP4BoxTest extends TestCase
{
    protected function setUp(): void
    {
        $finder = new ExecutableFinder();

        if (null === $finder->find('MP4Box')) {
            $this->markTestSkipped('Unable to detect MP4Box, required for this test');
        }
    }

    public function testProcess()
    {
        $file = sys_get_temp_dir() . '/' . uniqid('mp4box', true) . '.mp4';
        copy(__DIR__ . '/../../files/Video.mp4', $file);

        try {
            $mp4box = MP4Box::create();
            $mp4box->process($file);
            $this->assertFileExists($file);
        } finally {
            unlink($file);
        }
    }

    public function testProcessWithCustomOutput()
    {
        $out = __DIR__ . '/../../files/OutVideo.mp4';

        if (file_exists($out)) {
            unlink($out);
        }

        $mp4box = MP4Box::create();
        $mp4box->process(__DIR__ . '/../../files/Video.mp4', $out);
        $this->assertTrue(file_exists($out));
        unlink($out);
    }

    public function testProcessFail()
    {
        $this->expectException(RuntimeException::class);
        $mp4box = MP4Box::create();
        $mp4box->process(__DIR__ . '/../../files/WrongFile.mp4');
    }
}
