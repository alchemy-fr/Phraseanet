<?php

namespace Alchemy\Tests\Phrasea\SearchEngine;

use Alchemy\Phrasea\SearchEngine\SphinxSearch\SphinxSearchEngine;
use Alchemy\Phrasea\SearchEngine\SphinxSearch\ConfigurationPanel;

class SphinxSearchConfigurationPanelTest extends ConfigurationPanelAbstractTest
{
    public function getPanel()
    {
        return new ConfigurationPanel(new SphinxSearchEngine(self::$DI['app'], 'localhost', 9306, 'localhost', 9308), self::$DI['app']['conf']);
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
