<?php

namespace Alchemy\Tests\Phrasea\Border\Checker;

use Alchemy\Phrasea\Border\Checker\CheckerInterface;
use Alchemy\Phrasea\Border\Checker\Response;
use Alchemy\Tests\Tools\TranslatorMockTrait;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class ResponseTest extends \PhraseanetTestCase
{
    use TranslatorMockTrait;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $mock;
    /** @var Response */
    protected $object;

    public function setUp(): void
    {
        parent::setUp();
        $this->mock = $this->createMock(CheckerInterface::class);
        $this->object = new Response(true, $this->mock);
    }

    public function tearDown(): void
    {
        $this->mock = $this->object = null;
        parent::tearDown();
    }

    public function testIsOk()
    {
        $this->assertTrue($this->object->isOk());

        $this->object = new Response(false, $this->mock);
        $this->assertFalse($this->object->isOk());
    }

    public function testGetCheck()
    {
        $this->assertSame($this->mock, $this->object->getChecker());
    }

    public function testGetMessage()
    {
        $this->mock
            ->method('getMessage')
            ->will($this->returnValue('Hello World'));

        $this->object = new Response(true, $this->mock);

        $this->assertEquals('Hello World', $this->object->getMessage($this->createTranslatorMock()));
    }
}
