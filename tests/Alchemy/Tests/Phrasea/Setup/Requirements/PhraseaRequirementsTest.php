<?php

namespace Alchemy\Tests\Phrasea\Setup\Requirements;

use Alchemy\Phrasea\Setup\Requirements\PhraseaRequirements;

class PhraseaRequirementsTest extends RequirementsTestCase
{
    protected function provideRequirements()
    {
        return new PhraseaRequirements;
    }
}
