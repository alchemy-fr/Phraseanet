<?php

namespace Alchemy\Tests\Phrasea\Form\Configuration;

use Alchemy\Phrasea\Form\Configuration\MaintenanceFormType;
use Alchemy\Tests\Phrasea\Form\FormTestCase;

class MaintenanceFormTypeTest extends FormTestCase
{
    public function getForm()
    {
        return new MaintenanceFormType();
    }
}
