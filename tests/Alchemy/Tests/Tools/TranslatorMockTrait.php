<?php

namespace Alchemy\Tests\Tools;

trait TranslatorMockTrait
{
    public function createTranslatorMock()
    {
        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface', ['addResource', 'trans', 'transChoice', 'setLocale', 'getLocale', 'setFallbackLocales', 'addLoader']);
        $translator->expects($this->any())
            ->method('trans')
            ->will($this->returnCallback(function ($id) { return $id;}));

        return $translator;
    }
}
