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

use Alchemy\Phrasea\Utilities\Less\Compiler;

class CompilerTest extends \PHPUnit_Framework_TestCase
{
    public function testCompileSuccess()
    {
        $recessDriver = $this->getMock('Alchemy\BinaryDriver\BinaryInterface');
        $recessDriver->expects($this->once())->method('command');

        $filesystem = $this->getMock('Symfony\Component\Filesystem\Filesystem');
        $filesystem->expects($this->once())->method('mkdir');
        $filesystem->expects($this->once())->method('dumpFile');

        $compiler = new Compiler($filesystem, $recessDriver);

        $compiler->compile(__DIR__ . '/output.css', __FILE__);
    }
}
