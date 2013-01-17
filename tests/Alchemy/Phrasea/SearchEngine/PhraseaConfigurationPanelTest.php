<?php

namespace Alchemy\Phrasea\SearchEngine;

use Alchemy\Phrasea\SearchEngine\Phrasea\PhraseaEngine;
use Alchemy\Phrasea\SearchEngine\Phrasea\ConfigurationPanel;

require_once __DIR__ . '/ConfigurationPanelAbstractTest.php';

class PhraseaConfigurationPanelTest extends ConfigurationPanelAbstractTest
{
    /**
     * @covers Alchemy\Phrasea\SearchEngine\Phrasea\ConfigurationPanel
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
    }
    
    public function getPanel()
    {
        return new ConfigurationPanel(new PhraseaEngine(self::$DI['app']));
    }
}