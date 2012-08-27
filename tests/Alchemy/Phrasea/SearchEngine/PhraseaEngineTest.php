<?php

namespace Alchemy\Phrasea\SearchEngine;

use Alchemy\Phrasea\SearchEngine\Phrasea\PhraseaEngine;

require_once __DIR__ . '/SearchEngineAbstractTest.php';

class PhraseaEngineTest extends SearchEngineAbstractTest
{
    public function setUp()
    {
        parent::setUp();
        $this->searchEngine = new PhraseaEngine();
    }
}

