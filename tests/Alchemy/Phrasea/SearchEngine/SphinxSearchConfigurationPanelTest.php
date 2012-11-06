<?php

namespace Alchemy\Phrasea\SearchEngine;

use Alchemy\Phrasea\SearchEngine\SphinxSearch\SphinxSearchEngine;
use Alchemy\Phrasea\SearchEngine\SphinxSearch\ConfigurationPanel;

require_once __DIR__ . '/ConfigurationPanelAbstractTest.php';

class SphinxSearchConfigurationPanelTest extends ConfigurationPanelAbstractTest
{
    /**
     * @covers Alchemy\Phrasea\SearchEngine\SphinxSearch\ConfigurationPanel
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
    }
    
    public function getPanel()
    {
        return new ConfigurationPanel(new SphinxSearchEngine(self::$DI['app'], 'localhost', 9306, 'localhost', 9308));
    }

    public function testGetAVailableCharsets()
    {
        $charsets = $this->getPanel()->getAvailableCharsets();

        $this->assertInternalType('array', $charsets);
        foreach ($charsets as $name => $charset) {
            $this->assertInternalType('string', $name);
            $this->assertInstanceOf('Alchemy\Phrasea\SearchEngine\SphinxSearch\AbstractCharset', $charset);
        }
    }

    public function testGenerateSphinxConf()
    {
        $databoxes = self::$DI['app']['phraseanet.appbox']->get_databoxes();
        $configuration = $this->getPanel()->getConfiguration();
        
        $conf = $this->getPanel()->generateSphinxConf($databoxes, $configuration);
        $this->assertInternalType('string', $conf);
    }

}