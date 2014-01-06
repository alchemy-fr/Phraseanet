<?php

namespace Alchemy\Tests\Phrasea\Form\Configuration;

use Alchemy\Phrasea\Form\Configuration\ActionsFormType;
use Alchemy\Tests\Phrasea\Form\FormTestCase;

class ActionsFormTypeTest extends FormTestCase
{
    public function getForm()
    {
        return new ActionsFormType();
    }
}
