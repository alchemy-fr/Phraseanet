<?php

class patch_3803Test extends PhraseanetPHPUnitAbstract
{
    /**
     * @covers patch_3803::apply
     */
    public function testApplyInSphinxEnvironment()
    {
        $patch = new patch_3803();

        $appbox = $this->getMockBuilder('appbox')
            ->disableOriginalConstructor()
            ->getMock();

        $app = self::$DI['app'];

        $app['phraseanet.registry'] = $this->getMock('registryInterface');
        $app['phraseanet.registry']->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(function ($parameter) {
                switch ($parameter) {
                    case 'GV_sphinx':
                        return true;
                    case 'GV_sphinx_rt_port':
                        return 5678;
                    case 'GV_sphinx_rt_host':
                        return 'sphinx.rt_host';
                    case 'GV_sphinx_host':
                        return 'sphinx.host';
                    case 'GV_sphinx_port':
                        return 1234;
                    default:
                        throw new \InvalidArgumentException(sprintf('%s is missing, test case not ready', $parameter));
                }
            }));

        $catchConfiguration = $catchSEConf = null;

        $app['phraseanet.configuration'] = $this->getMockBuilder('Alchemy\Phrasea\Core\Configuration')
            ->disableOriginalConstructor()
            ->getMock();
        $app['phraseanet.configuration']->expects($this->once())
            ->method('getConfigurations')
            ->will($this->returnValue(array('environment' => 'prod', 'prod'        => array(), 'dev' => array())));
        $app['phraseanet.configuration']->expects($this->once())
            ->method('setConfigurations')
            ->will($this->returnCallback(function($configuration) use (&$catchConfiguration) {
                $catchConfiguration = $configuration;
            }));

        $panel = $this->getMock('Alchemy\Phrasea\SearchEngine\ConfigurationPanelInterface');
        $panel->expects($this->once())
            ->method('saveConfiguration')
            ->will($this->returnCallback(function($json) use (&$catchSEConf){
                $catchSEConf = $json;
            }));
        $panel->expects($this->once())
            ->method('getConfiguration')
            ->will($this->returnValue(array()));

        $app['phraseanet.SE'] = $this->getMock('Alchemy\Phrasea\SearchEngine\SearchEngineInterface');
        $app['phraseanet.SE']->expects($this->any())
            ->method('getConfigurationPanel')
            ->will($this->returnValue($panel));

        $this->assertTrue($patch->apply($appbox, $app));

        $upgrade = 0;
        foreach ($catchConfiguration as $env => $conf) {
            if (in_array($env, array('environment', 'key'))) {
                continue;
            }
            $this->assertArrayHasKey('search-engine', $conf);
            $upgrade++;
        }

        $this->assertEquals(2, $upgrade);
        $this->assertArrayHasKey('port', $catchSEConf);
        $this->assertArrayHasKey('host', $catchSEConf);
        $this->assertArrayHasKey('rt_port', $catchSEConf);
        $this->assertArrayHasKey('rt_host', $catchSEConf);
        $this->assertEquals(5678, $catchSEConf['rt_port']);
        $this->assertEquals('sphinx.rt_host', $catchSEConf['rt_host']);
        $this->assertEquals(1234, $catchSEConf['port']);
        $this->assertEquals('sphinx.host', $catchSEConf['host']);
    }

    /**
     * @covers patch_3803::apply
     */
    public function testApplyInPhraseaEnvironment()
    {
        $patch = new patch_3803();

        $appbox = $this->getMockBuilder('appbox')
            ->disableOriginalConstructor()
            ->getMock();

        $app = self::$DI['app'];

        $app['phraseanet.registry'] = $this->getMock('registryInterface');
        $app['phraseanet.registry']->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(function ($parameter) {
                switch ($parameter) {
                    case 'GV_sphinx':
                        return false;
                    case 'GV_phrasea_sort':
                        return 'custom-sort';
                    default:
                        throw new \InvalidArgumentException(sprintf('%s is missing, test case not ready', $parameter));
                }
            }));

        $catchConfiguration = $catchPhraseaConf = null;

        $app['phraseanet.configuration'] = $this->getMockBuilder('Alchemy\Phrasea\Core\Configuration')
            ->disableOriginalConstructor()
            ->getMock();
        $app['phraseanet.configuration']->expects($this->once())
            ->method('getConfigurations')
            ->will($this->returnValue(array('environment' => 'prod', 'prod'        => array(), 'dev' => array())));
        $app['phraseanet.configuration']->expects($this->once())
            ->method('setConfigurations')
            ->will($this->returnCallback(function($configuration) use (&$catchConfiguration) {
                $catchConfiguration = $configuration;
            }));

        $panel = $this->getMock('Alchemy\Phrasea\SearchEngine\ConfigurationPanelInterface');
        $panel->expects($this->once())
            ->method('saveConfiguration')
            ->will($this->returnCallback(function($json) use (&$catchSEConf){
                $catchSEConf = $json;
            }));
        $panel->expects($this->once())
            ->method('getConfiguration')
            ->will($this->returnValue(array()));

        $app['phraseanet.SE'] = $this->getMock('Alchemy\Phrasea\SearchEngine\SearchEngineInterface');
        $app['phraseanet.SE']->expects($this->any())
            ->method('getConfigurationPanel')
            ->will($this->returnValue($panel));

        $this->assertTrue($patch->apply($appbox, $app));

        $upgrade = 0;
        foreach ($catchConfiguration as $env => $conf) {
            if (in_array($env, array('environment', 'key'))) {
                continue;
            }
            $this->assertArrayHasKey('search-engine', $conf);
            $upgrade++;
        }

        $this->assertEquals(2, $upgrade);

        $this->assertArrayHasKey('default_sort', $catchSEConf);
        $this->assertEquals('custom-sort', $catchSEConf['default_sort']);
    }
}
