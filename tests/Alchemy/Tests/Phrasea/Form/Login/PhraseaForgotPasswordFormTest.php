<?php

namespace Alchemy\Tests\Phrasea\Form\Login;

use Alchemy\Phrasea\Form\Login\PhraseaForgotPasswordForm;;
use Alchemy\Tests\Phrasea\Form\FormTestCase;

/**
 * @group functional
 * @group legacy
 */
class PhraseaForgotPasswordFormTest extends FormTestCase
{
    protected function getForm()
    {
        return new PhraseaForgotPasswordForm();
    }
}
