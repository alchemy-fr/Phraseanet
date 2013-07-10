<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

        $builder->build(array( __FILE__ => __DIR__ . '/output.css'));
    }
}
