<?php

namespace Alchemy\Tests\Phrasea\Form\Configuration;

use Alchemy\Phrasea\Form\Configuration\RegistrationFormType;
use Alchemy\Tests\Phrasea\Form\FormTestCase;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class RegistrationFormTypeTest extends FormTestCase
{
    public function getForm()
    {
        return new RegistrationFormType(self::$DI['app']['translator']);
    }
}
