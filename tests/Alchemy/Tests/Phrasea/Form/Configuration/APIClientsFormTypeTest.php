<?php

namespace Alchemy\Tests\Phrasea\Form\Configuration;

use Alchemy\Phrasea\Form\Configuration\APIClientsFormType;
use Alchemy\Tests\Phrasea\Form\FormTestCase;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class APIClientsFormTypeTest extends FormTestCase
{
    public function getForm()
    {
        return new APIClientsFormType(self::$DI['app']['translator']);
    }
}
