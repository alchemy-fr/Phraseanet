<?php

namespace Alchemy\Tests\Phrasea\Form\Configuration;

use Alchemy\Phrasea\Form\Configuration\ActionsFormType;
use Alchemy\Tests\Phrasea\Form\FormTestCase;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class ActionsFormTypeTest extends FormTestCase
{
    public function getForm()
    {
        return new ActionsFormType(self::$DI['app']['translator']);
    }
}
