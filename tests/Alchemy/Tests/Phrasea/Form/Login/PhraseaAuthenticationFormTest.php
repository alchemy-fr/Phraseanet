<?php

namespace Alchemy\Tests\Phrasea\Form\Login;

use Alchemy\Phrasea\Form\Login\PhraseaAuthenticationForm;
use Alchemy\Tests\Phrasea\Form\FormTestCase;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class PhraseaAuthenticationFormTest extends FormTestCase
{
    protected function getForm()
    {
        return new PhraseaAuthenticationForm(self::$DI['app']);
    }
}
