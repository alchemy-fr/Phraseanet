<?php

namespace Alchemy\Tests\Phrasea\Setup\Probe;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class SearchEngineProbeTest extends ProbeTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        self::$DI['app']['phraseanet.SE'] = $this->createSearchEngineMock();
    }

    protected function getClassName()
    {
        return 'Alchemy\Phrasea\Setup\Probe\SearchEngineProbe';
    }
}
