<?php

namespace Alchemy\Tests\Phrasea\Form\Configuration;

use Alchemy\Phrasea\Form\Configuration\RegistrationFormType;
use Alchemy\Tests\Phrasea\Form\FormTestCase;

class RegistrationFormTypeTest extends FormTestCase
{
    public function getForm()
    {
        return new RegistrationFormType();
    }
}
