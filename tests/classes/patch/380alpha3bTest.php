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

        $app['conf'] = $this->getMockBuilder('Alchemy\Phrasea\Core\Configuration\PropertyAccess')
            ->disableOriginalConstructor()
            ->getMock();
        $app['conf']->expects($this->once())
            ->method('set')
            ->with(['main', 'search-engine'], [
                'type'    => 'Alchemy\Phrasea\SearchEngine\Phrasea\PhraseaEngine',
                'options' => [],
            ]);

        $this->assertTrue($patch->apply($appbox, $app));
    }
}
