<?php

namespace Alchemy\Tests\Phrasea\Form\Login;

use Alchemy\Phrasea\Form\Login\PhraseaRenewPasswordForm;;
use Alchemy\Tests\Phrasea\Form\FormTestCase;

/**
 * @group functional
 * @group legacy
 */
class PhraseaRenewPasswordFormTest extends FormTestCase
{
    protected function getForm()
    {
        return new PhraseaRenewPasswordForm();
    }
}
