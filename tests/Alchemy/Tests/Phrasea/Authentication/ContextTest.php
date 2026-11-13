<?php

namespace Alchemy\Tests\Phrasea\Authentication;

use Alchemy\Phrasea\Authentication\Context;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class ContextTest extends \PhraseanetTestCase
{
    public function testWithValidCOntext()
    {
        $contextValue = Context::CONTEXT_GUEST;
        $context = new Context($contextValue);

        $this->assertEquals($contextValue, $context->getContext());
        $context->setContext(Context::CONTEXT_OAUTH2_NATIVE);
        $this->assertEquals(Context::CONTEXT_OAUTH2_NATIVE, $context->getContext());
    }

    public function testWithInvalidCOntext()
    {
        $this->expectException(\Alchemy\Phrasea\Exception\InvalidArgumentException::class);
        new Context('No context like this');
    }
}
