<?php

namespace Alchemy\Tests\Phrasea\Setup\Probe;

class SearchEngineProbeTest extends ProbeTestCase
{
    public function setUp()
    {
        parent::setUp();
        self::$DI['app']['phraseanet.SE'] = $this->createSearchEngineMock();
    }

    protected function getClassName()
    {
        return 'Alchemy\Phrasea\Setup\Probe\SearchEngineProbe';
    }
}
