<?php

namespace Alchemy\Tests\Phrasea\Form\Login;

use Alchemy\Phrasea\Form\Login\PhraseaAuthenticationForm;
use Alchemy\Tests\Phrasea\Form\FormTestCase;

class PhraseaAuthenticationFormTest extends FormTestCase
{
    protected function getForm()
    {
        return new PhraseaAuthenticationForm();
    }
}
