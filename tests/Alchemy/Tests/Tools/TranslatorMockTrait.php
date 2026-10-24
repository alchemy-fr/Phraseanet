<?php

namespace Alchemy\Tests\Tools;

use Symfony\Component\Translation\Translator;

trait TranslatorMockTrait
{
    public function createTranslatorMock()
    {
        $translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addResource', 'trans', 'setLocale', 'getLocale', 'setFallbackLocales', 'addLoader'])
            ->getMock();
        $translator
            ->method('trans')
            ->willReturnCallback(function ($id) { return $id; });

        return $translator;
    }
}
