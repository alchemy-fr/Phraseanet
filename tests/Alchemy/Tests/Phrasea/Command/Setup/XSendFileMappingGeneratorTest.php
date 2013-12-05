<?php

namespace Alchemy\Tests\Phrasea\Command\Setup;

use Alchemy\Phrasea\Command\Setup\XSendFileMappingGenerator;

class XSendFileMappingGeneratorTest extends \PhraseanetPHPUnitAbstract
{
    /**
     * @dataProvider provideVariousOptions
     */
    public function testRunWithoutProblems($option)
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $input->expects($this->any())
            ->method('getArgument')
            ->with('type')
            ->will($this->returnValue('nginx'));

        $input->expects($this->any())
            ->method('getOption')
            ->with($this->isType('string'))
            ->will($this->returnValue($option));

        $command = new XSendFileMappingGenerator();

        self::$DI['cli']['monolog'] = self::$DI['cli']->share(function () {
            return $this->getMockBuilder('Monolog\Logger')->disableOriginalConstructor()->getMock();
        });
        self::$DI['cli']['conf'] = $this->getMockBuilder('Alchemy\Phrasea\Core\Configuration\PropertyAccess')
            ->disableOriginalConstructor()
            ->getMock();
        if ($option) {

            self::$DI['cli']['conf']->expects($this->once())
                ->method('set')
                ->with('xsendfile');
        } else {
            self::$DI['cli']['conf']->expects($this->never())
                ->method('set');
        }
        $command->setContainer(self::$DI['cli']);

        $this->assertEquals(0, $command->execute($input, $output));
    }

    public function testRunWithProblem()
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $logger = $this->getMockBuilder('Monolog\Logger')
                  ->disableOriginalConstructor()
                  ->getMock();
        $logger->expects($this->once())
            ->method('error');

        self::$DI['cli']['monolog'] = self::$DI['cli']->share(function () use ($logger) {
            return $logger;
        });

        $input->expects($this->any())
            ->method('getArgument')
            ->with('type')
            ->will($this->returnValue(null));

        $command = new XSendFileMappingGenerator();
        $command->setContainer(self::$DI['cli']);
        $this->setExpectedException('Alchemy\Phrasea\Exception\InvalidArgumentException');
        $command->execute($input, $output);
    }

    public function provideVariousOptions()
    {
        return [
            [true],
            [false],
        ];
    }
}
