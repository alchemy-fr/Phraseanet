<?php

namespace Alchemy\Tests\Phrasea\Form\Configuration;

use Alchemy\Phrasea\Form\Configuration\SearchEngineFormType;
use Alchemy\Tests\Phrasea\Form\FormTestCase;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class SearchEngineFormTypeTest extends FormTestCase
{
    public function getForm()
    {
        return new SearchEngineFormType();
    }

    protected function getFormOptions()
    {
        return ['translator' => self::$DI['app']['translator']];
    }
}
