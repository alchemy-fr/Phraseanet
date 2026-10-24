<?php

namespace Alchemy\Tests\Phrasea\Form\Configuration;

use Alchemy\Phrasea\Form\Configuration\MainConfigurationFormType;
use Alchemy\Tests\Phrasea\Form\FormTestCase;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class MainConfigurationFormTypeTest extends FormTestCase
{
    public function getForm()
    {
        return new MainConfigurationFormType();
    }

    protected function getFormOptions()
    {
        return ['translator' => self::$DI['app']['translator']];
    }
}
