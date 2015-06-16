<?php

namespace Alchemy\Tests\Phrasea\Setup\Probe;

/**
 * @group functional
 * @group legacy
 */
class CacheServerProbeTest extends ProbeTestCase
{
    protected function getClassName()
    {
        return 'Alchemy\Phrasea\Setup\Probe\CacheServerProbe';
    }
}
