<?php

namespace MediaAlchemyst\Tests\Specification;

use PHPUnit\Framework\TestCase;
use MediaAlchemyst\Specification\Animation;
use MediaAlchemyst\Specification\SpecificationInterface;

class AnimationTest extends TestCase
{
    /**
     * @var Animation
     */
    protected $object;

    protected function setUp(): void
    {
        $this->object = new Animation();
    }

    public function testGetType()
    {
        $this->assertEquals(SpecificationInterface::TYPE_ANIMATION, $this->object->getType());
    }

    public function testSetDelay()
    {
        $this->object->setDelay(800);
        $this->assertEquals(800, $this->object->getDelay());
    }
}
