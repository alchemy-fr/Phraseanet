<?php

namespace Alchemy\Tests\Phrasea\Authentication;

use Alchemy\Phrasea\Authentication\Context;

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

    /**
     * @expectedException \Alchemy\Phrasea\Exception\InvalidArgumentException
     */
    public function testWithInvalidCOntext()
    {
        new Context('No context like this');
    }
}
