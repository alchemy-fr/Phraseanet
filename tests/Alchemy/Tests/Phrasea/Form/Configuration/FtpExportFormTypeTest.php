<?php

namespace Alchemy\Tests\Phrasea\Form\Configuration;

use Alchemy\Phrasea\Form\Configuration\FtpExportFormType;
use Alchemy\Tests\Phrasea\Form\FormTestCase;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class FtpExportFormTypeTest extends FormTestCase
{
    public function getForm()
    {
        return new FtpExportFormType(self::$DI['app']['translator']);
    }
}
