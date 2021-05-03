<?php

namespace Alchemy\Tests\Phrasea\Form\Configuration;

use Alchemy\Phrasea\Form\Configuration\ActionsFormType;
use Alchemy\Tests\Phrasea\Form\FormTestCase;

/**
 * @group functional
 * @group legacy
 */
class ActionsFormTypeTest extends FormTestCase
{
    public function getForm()
    {
        return new ActionsFormType(self::$DI['app']['translator']);
    }
}
