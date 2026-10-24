<?php

namespace Alchemy\Tests\Phrasea\Form\Login;

use Alchemy\Phrasea\Form\Login\PhraseaRecoverPasswordForm;;
use Alchemy\Tests\Phrasea\Form\FormTestCase;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class PhraseaRecoverPasswordFormTest extends FormTestCase
{
    protected function getForm()
    {
        return new PhraseaRecoverPasswordForm();
    }

    protected function getFormOptions()
    {
        return ['token_repository' => self::$DI['app']['repo.tokens']];
    }
}
