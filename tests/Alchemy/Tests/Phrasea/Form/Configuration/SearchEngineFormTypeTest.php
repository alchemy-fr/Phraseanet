<?php

namespace Alchemy\Tests\Phrasea\Form\Configuration;

use Alchemy\Phrasea\Form\Configuration\SearchEngineFormType;
use Alchemy\Tests\Phrasea\Form\FormTestCase;

/**
 * @group functional
 * @group legacy
 */
class SearchEngineFormTypeTest extends FormTestCase
{
    public function getForm()
    {
        return new SearchEngineFormType(self::$DI['app']['translator']);
    }
}
