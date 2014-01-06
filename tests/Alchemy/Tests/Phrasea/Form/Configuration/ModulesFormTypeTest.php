<?php

namespace Alchemy\Tests\Phrasea\Form\Configuration;

use Alchemy\Phrasea\Form\Configuration\ModulesFormType;
use Alchemy\Tests\Phrasea\Form\FormTestCase;

class ModulesFormTypeTest extends FormTestCase
{
    public function getForm()
    {
        return new ModulesFormType();
    }
}
