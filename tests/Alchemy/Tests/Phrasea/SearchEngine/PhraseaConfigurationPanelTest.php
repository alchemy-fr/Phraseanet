<?php

namespace Alchemy\Tests\Phrasea\SearchEngine;

use Alchemy\Phrasea\SearchEngine\Phrasea\PhraseaEngine;
use Alchemy\Phrasea\SearchEngine\Phrasea\ConfigurationPanel;
use Alchemy\Tests\Phrasea\SearchEngine\ConfigurationPanelAbstractTest;

class PhraseaConfigurationPanelTest extends ConfigurationPanelAbstractTest
{
    public function getPanel()
    {
        return new ConfigurationPanel(new PhraseaEngine(self::$DI['app']), self::$DI['app']['configuration.store']);
    }
}
