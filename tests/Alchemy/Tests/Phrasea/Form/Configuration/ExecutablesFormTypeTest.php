<?php

namespace Alchemy\Tests\Phrasea\Form\Configuration;

use Alchemy\Phrasea\Form\Configuration\ExecutablesFormType;
use Alchemy\Tests\Phrasea\Form\FormTestCase;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class ExecutablesFormTypeTest extends FormTestCase
{
    public function getForm()
    {
        return new ExecutablesFormType(self::$DI['app']['translator']);
    }
}
