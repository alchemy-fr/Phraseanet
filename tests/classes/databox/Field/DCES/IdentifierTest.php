<?php

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class databox_Field_DCES_IdentifierTest extends databox_Field_DCES_DCESTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->object = new databox_Field_DCES_Identifier;
    }
}
