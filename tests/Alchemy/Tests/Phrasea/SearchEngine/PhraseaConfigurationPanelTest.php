<?php

namespace Alchemy\Tests\Phrasea\SearchEngine;

use Alchemy\Phrasea\SearchEngine\Phrasea\PhraseaEngine;
use Alchemy\Phrasea\SearchEngine\Phrasea\ConfigurationPanel;

class PhraseaConfigurationPanelTest extends ConfigurationPanelAbstractTest
{
    public function setUp()
    {
        if (!extension_loaded('phrasea2')) {
            $this->markTestSkipped('Phrasea extension is not loaded');
        }

        parent::setUp();
    }

    public function getPanel()
    {
        return new ConfigurationPanel(new PhraseaEngine(self::$DI['app']), self::$DI['app']['conf']);
    }
}
