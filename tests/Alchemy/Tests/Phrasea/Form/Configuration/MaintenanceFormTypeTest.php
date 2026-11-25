<?php

namespace Alchemy\Tests\Phrasea\Form\Configuration;

use Alchemy\Phrasea\Form\Configuration\MaintenanceFormType;
use Alchemy\Tests\Phrasea\Form\FormTestCase;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class MaintenanceFormTypeTest extends FormTestCase
{
    public function getForm()
    {
        return new MaintenanceFormType();
    }
}
