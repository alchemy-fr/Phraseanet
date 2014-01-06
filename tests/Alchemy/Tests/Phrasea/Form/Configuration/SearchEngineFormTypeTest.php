<?php

namespace Alchemy\Tests\Phrasea\Form\Configuration;

use Alchemy\Phrasea\Form\Configuration\SearchEngineFormType;
use Alchemy\Tests\Phrasea\Form\FormTestCase;

class SearchEngineFormTypeTest extends FormTestCase
{
    public function getForm()
    {
        return new SearchEngineFormType();
    }
}
