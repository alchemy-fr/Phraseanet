<?php

namespace Alchemy\Tests\Phrasea\Form\Login;

use Alchemy\Phrasea\Form\Login\PhraseaAuthenticationWithMappingForm;;
use Alchemy\Tests\Phrasea\Form\FormTestCase;

class PhraseaAuthenticationWithMappingFormTest extends FormTestCase
{
    protected function getForm()
    {
        return new PhraseaAuthenticationWithMappingForm();
    }
}
