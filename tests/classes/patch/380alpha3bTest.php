<?php

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class patch_380alpha3bTest extends \PhraseanetTestCase
{
    public function testApplyInPhraseaEnvironment()
    {
        $patch = new patch_380alpha3b();

        $appbox = $this->createStub('appbox');

        $app = $this->getApplication();

        $app['conf'] = $this->createMock('Alchemy\Phrasea\Core\Configuration\PropertyAccess');
        $app['conf']->expects($this->once())
            ->method('set')
            ->with(['main', 'search-engine'], [
                'type'    => 'Alchemy\Phrasea\SearchEngine\Phrasea\PhraseaEngine',
                'options' => [],
            ]);

        $this->assertTrue($patch->apply($appbox, $app));
    }
}
