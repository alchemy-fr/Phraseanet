<?php

namespace Alchemy\Tests\Phrasea\Form\Configuration;

use Alchemy\Phrasea\Form\Configuration\APIClientsFormType;
use Alchemy\Tests\Phrasea\Form\FormTestCase;

/**
 * @group functional
 * @group legacy
 */
class APIClientsFormTypeTest extends FormTestCase
{
    public function getForm()
    {
        return new APIClientsFormType(self::$DI['app']['translator']);
    }
}
