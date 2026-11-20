<?php

namespace Alchemy\Tests\Phrasea\Setup\Probe;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class FilesystemProbeTest extends ProbeTestCase
{
    protected function getClassName()
    {
        return 'Alchemy\Phrasea\Setup\Probe\FilesystemProbe';
    }
}
