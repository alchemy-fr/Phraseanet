<?php

class patch_3804Test extends PhraseanetPHPUnitAbstract
{
    /**
     * @covers patch_3804::apply
     */
    public function testApply()
    {
        $app = self::$DI['app'];

        $patch = new patch_3804();

        $appbox = $this->getMockBuilder('appbox')
            ->disableOriginalConstructor()
            ->getMock();

        $catchConfiguration = null;

        $app['phraseanet.configuration'] = $this->getMockBuilder('Alchemy\Phrasea\Core\Configuration')
            ->disableOriginalConstructor()
            ->getMock();

        $app['phraseanet.configuration']->expects($this->once())
            ->method('getConfigurations')
            ->will($this->returnValue(array(
                'environment' => 'prod',
                'prod'        => array(),
                'dev'         => array()
            )));
        $app['phraseanet.configuration']->expects($this->once())
            ->method('setConfigurations')
            ->will($this->returnCallback(function($configuration) use (&$catchConfiguration) {
                $catchConfiguration = $configuration;
            }));

        $app['phraseanet.configuration']->expects($this->once())
            ->method('getServices')
            ->will($this->returnValue(array(
                'SearchEngine' => array(),
            )));

        $app['phraseanet.configuration']->expects($this->once())
            ->method('resetServices')
            ->with($this->equalTo('TaskManager'));

        $this->assertTrue($patch->apply($appbox, $app));

        $upgrade = 0;
        foreach ($catchConfiguration as $env => $conf) {
            if (in_array($env, array('environment', 'key'))) {
                continue;
            }
            $this->assertArrayHasKey('task-manager', $conf);
            $upgrade++;
        }

        $this->assertEquals(2, $upgrade);
    }
}
