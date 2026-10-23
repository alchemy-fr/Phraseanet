<?php

namespace Alchemy\Tests\Phrasea\Application;

use Symfony\Component\Process\Process;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class ConsoleAPITest extends \PhraseanetTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('provideConsoleNames')]
    public function testThatCommandsExitWithZero($console)
    {
        $process = new Process([__DIR__, '/../../../../../bin/', $console]);
        $process->run();

        $this->assertSame(0, $process->getExitCode());
    }

    public static function provideConsoleNames()
    {
        return [
            ['console'],
            ['setup'],
            ['developer'],
        ];
    }
}
