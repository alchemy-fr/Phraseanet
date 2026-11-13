<?php

namespace MP4Box\Tests;

use Symfony\Component\Process\ExecutableFinder;
use MP4Box\Exception\InvalidFileArgumentException;
use MP4Box\MP4Box;
use Alchemy\BinaryDriver\BinaryDriverTestCase;

class MP4BoxTest extends BinaryDriverTestCase
{
    protected function setUp(): void
    {
        $finder = new ExecutableFinder();

        if (null === $finder->find('MP4Box')) {
            $this->markTestSkipped('Unable to detect MP4Box, required for this test');
        }
    }

    public function testProcessOutputWithCustomOutput()
    {
        $input = __DIR__ . '/../../files/Video.mp4';
        $out = 'Output.mp4';

        $mp4box = MP4Box::create();

        $factory = $this->createProcessBuilderFactoryMock();
        $process = $this->createProcessMock(1, true);

        $factory
            ->expects($this->once())
            ->method('create')
            ->with(array(
                '-quiet',
                '-inter',
                '500',
                '-tmp',
                dirname($input),
                $input,
                '-out',
                $out,
            ))
            ->willReturn($process);

        $mp4box->setProcessBuilderFactory($factory);

        $mp4box->process(__DIR__ . '/../../files/Video.mp4', $out);
    }

    public function testProcessOnNonUnexistingFile()
    {
        $this->expectException(InvalidFileArgumentException::class);
        $mp4box = MP4Box::create();
        $mp4box->process(__DIR__ . '/../../files/Unknown');
    }
}
