<?php

namespace Alchemy\Tests\Phrasea\Form\Configuration;

use Alchemy\Phrasea\Form\Configuration\ClassicFormType;
use Alchemy\Tests\Phrasea\Form\FormTestCase;

class ClassicFormTypeTest extends FormTestCase
{
    public function getForm()
    {
        return new ClassicFormType();
    }
}
