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

        $app['phraseanet.configuration'] = $this->getMock('Alchemy\Phrasea\Core\Configuration\ConfigurationInterface');
        $app['phraseanet.configuration']->expects($this->once())
            ->method('setDefault')
            ->with('main', 'task-manager');

        $this->assertTrue($patch->apply($appbox, $app));
    }
}
