<?php

namespace Alchemy\Tests\Phrasea\Form\Configuration;

use Alchemy\Phrasea\Form\Configuration\MaintenanceFormType;
use Alchemy\Tests\Phrasea\Form\FormTestCase;

/**
 * @group functional
 * @group legacy
 */
class MaintenanceFormTypeTest extends FormTestCase
{
    public function getForm()
    {
        return new MaintenanceFormType();
    }
}
