<?php

namespace Alchemy\Tests\Phrasea\Form\Configuration;

use Alchemy\Phrasea\Form\Configuration\ModulesFormType;
use Alchemy\Tests\Phrasea\Form\FormTestCase;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class ModulesFormTypeTest extends FormTestCase
{
    public function getForm()
    {
        return new ModulesFormType(self::$DI['app']['translator']);
    }
}
