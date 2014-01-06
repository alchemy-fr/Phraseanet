<?php

namespace Alchemy\Tests\Phrasea\Form\Configuration;

use Alchemy\Phrasea\Form\Configuration\APIClientsFormType;
use Alchemy\Tests\Phrasea\Form\FormTestCase;

class APIClientsFormTypeTest extends FormTestCase
{
    public function getForm()
    {
        return new APIClientsFormType();
    }
}
