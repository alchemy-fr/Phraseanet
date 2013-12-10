<?php

class patch_380alpha3bTest extends \PhraseanetTestCase
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

        $app['configuration.store'] = $this->getMock('Alchemy\Phrasea\Core\Configuration\ConfigurationInterface');
        $app['configuration.store']->expects($this->once())
            ->method('setDefault')
            ->with('main', 'search-engine');

        $this->assertTrue($patch->apply($appbox, $app));
    }
}
