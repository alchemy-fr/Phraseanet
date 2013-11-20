<?php

namespace Alchemy\Tests\Phrasea\Utilities\Compiler;

use Alchemy\Phrasea\Utilities\Less\Builder;

class BuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testBuildSuccess()
    {
        $compiler = $this->getMockBuilder('Alchemy\Phrasea\Utilities\Less\Compiler')
            ->disableOriginalConstructor()
            ->getMock();
        $compiler->expects($this->once())->method('compile');

        $filesystem = $this->getMock('Symfony\Component\Filesystem\Filesystem');
        $filesystem->expects($this->once())->method('mkdir');

        $builder = new Builder($compiler, $filesystem);

        $builder->build([ __FILE__ => __DIR__ . '/output.css']);
    }
}
