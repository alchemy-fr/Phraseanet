<?php

class patch_380alpha3bTest extends PhraseanetPHPUnitAbstract
{
    /**
     * @covers patch_380alpha3b::apply
     */
    public function testApplyInPhraseaEnvironment()
    {
        $patch = new patch_380alpha3b();

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
