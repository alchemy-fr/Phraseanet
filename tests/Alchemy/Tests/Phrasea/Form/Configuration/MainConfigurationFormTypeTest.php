<?php

namespace Alchemy\Tests\Phrasea\Form\Configuration;

use Alchemy\Phrasea\Form\Configuration\MainConfigurationFormType;
use Alchemy\Tests\Phrasea\Form\FormTestCase;

class MainConfigurationFormTypeTest extends FormTestCase
{
    public function getForm()
    {
        return new MainConfigurationFormType(self::$DI['app']['translator'], ['fr' => 'french']);
    }
}
