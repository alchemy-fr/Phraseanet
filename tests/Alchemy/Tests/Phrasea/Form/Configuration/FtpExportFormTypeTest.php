<?php

namespace Alchemy\Tests\Phrasea\Form\Configuration;

use Alchemy\Phrasea\Form\Configuration\FtpExportFormType;
use Alchemy\Tests\Phrasea\Form\FormTestCase;

/**
 * @group functional
 * @group legacy
 */
class FtpExportFormTypeTest extends FormTestCase
{
    public function getForm()
    {
        return new FtpExportFormType(self::$DI['app']['translator']);
    }
}
