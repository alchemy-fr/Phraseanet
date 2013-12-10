<?php

namespace Alchemy\Tests\Phrasea\Utilities\Compiler;

use Alchemy\Phrasea\Utilities\Less\Compiler;
use Alchemy\BinaryDriver\Exception\ExecutionFailureException;

class CompilerTest extends \PhraseanetTestCase
{
    public function testCompileSuccess()
    {
        $recessDriver = $this->getMockBuilder('Alchemy\Phrasea\Command\Developer\Utils\RecessDriver')
            ->disableOriginalConstructor()
            ->getMock();
        $recessDriver->expects($this->once())->method('command');

        $filesystem = $this->getMock('Symfony\Component\Filesystem\Filesystem');
        $filesystem->expects($this->once())->method('mkdir');
        $filesystem->expects($this->once())->method('dumpFile');

        $compiler = new Compiler($filesystem, $recessDriver);

        $compiler->compile(__DIR__ . '/output.css', __FILE__);
    }

    /**
     * @expectedException \Alchemy\Phrasea\Exception\RuntimeException
     */
    public function testCompileFileNotExists()
    {
        $recessDriver = $this->getMockBuilder('Alchemy\Phrasea\Command\Developer\Utils\RecessDriver')
            ->disableOriginalConstructor()
            ->getMock();

        $filesystem = $this->getMock('Symfony\Component\Filesystem\Filesystem');
        $filesystem->expects($this->once())->method('mkdir');

        $compiler = new Compiler($filesystem, $recessDriver);

        $compiler->compile(__DIR__ . '/output.css', 'not_existsing_file');
    }

   /**
     * @expectedException \Alchemy\Phrasea\Exception\RuntimeException
     */
    public function testCompileExecutionFailure()
    {
        $recessDriver = $this->getMockBuilder('Alchemy\Phrasea\Command\Developer\Utils\RecessDriver')
            ->disableOriginalConstructor()
            ->getMock();

        $recessDriver->expects($this->once())->method('command')->will(
            $this->throwException(new ExecutionFailureException())
        );

        $filesystem = $this->getMock('Symfony\Component\Filesystem\Filesystem');

        $compiler = new Compiler($filesystem, $recessDriver);

        $compiler->compile(__DIR__ . '/output.css', __FILE__);
    }
}
