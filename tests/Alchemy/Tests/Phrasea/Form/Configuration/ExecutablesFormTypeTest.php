<?php

namespace Alchemy\Tests\Phrasea\Form\Configuration;

use Alchemy\Phrasea\Form\Configuration\ExecutablesFormType;
use Alchemy\Tests\Phrasea\Form\FormTestCase;

class ExecutablesFormTypeTest extends FormTestCase
{
    public function getForm()
    {
        return new ExecutablesFormType(self::$DI['app']['translator']);
    }
}
