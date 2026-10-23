<?php

namespace Alchemy\Tests\Phrasea\Form\Login;

use Alchemy\Phrasea\Form\Login\PhraseaForgotPasswordForm;;
use Alchemy\Tests\Phrasea\Form\FormTestCase;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class PhraseaForgotPasswordFormTest extends FormTestCase
{
    protected function getForm()
    {
        return new PhraseaForgotPasswordForm();
    }
}
