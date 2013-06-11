<?php

class patch_3803Test extends PhraseanetPHPUnitAbstract
{
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

        $app['phraseanet.configuration'] = $this->getMock('Alchemy\Phrasea\Core\Configuration\ConfigurationInterface');
        $app['phraseanet.configuration']->expects($this->once())
            ->method('setDefault')
            ->with('main', 'search-engine');

        $this->assertTrue($patch->apply($appbox, $app));
    }
}
