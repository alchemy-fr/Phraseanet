<?php

namespace Alchemy\Tests\Phrasea\Form\Configuration;

use Alchemy\Phrasea\Form\Configuration\ClassicFormType;
use Alchemy\Tests\Phrasea\Form\FormTestCase;

/**
 * @group functional
 * @group legacy
 */
class ClassicFormTypeTest extends FormTestCase
{
    public function getForm()
    {
        return new ClassicFormType();
    }
}
