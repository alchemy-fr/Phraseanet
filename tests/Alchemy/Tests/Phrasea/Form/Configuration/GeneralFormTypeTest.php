<?php

namespace Alchemy\Tests\Phrasea\Form\Configuration;

use Alchemy\Phrasea\Form\Configuration\GeneralFormType;
use Alchemy\Tests\Phrasea\Form\FormTestCase;

class GeneralFormTypeTest extends FormTestCase
{
    public function getForm()
    {
        return new GeneralFormType(['fr' => 'french']);
    }
}
