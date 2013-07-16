<?php

namespace Alchemy\Tests\Phrasea\Command;

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
        $phpunit = $this;

        self::$DI['cli']['monolog'] = self::$DI['cli']->share(function () use ($phpunit) {
            return $phpunit->getMockBuilder('Monolog\Logger')->disableOriginalConstructor()->getMock();
        });
        self::$DI['cli']['phraseanet.configuration'] = $this->getMock('Alchemy\Phrasea\Core\Configuration\ConfigurationInterface');
        if ($option) {
            self::$DI['cli']['phraseanet.configuration']->expects($this->once())
                ->method('offsetSet')
                ->with('xsendfile');
        } else {
            self::$DI['cli']['phraseanet.configuration']->expects($this->never())
                ->method('offsetSet');
        }
        $command->setContainer(self::$DI['cli']);

        $this->assertEquals(0, $command->execute($input, $output));
    }

    public function testRunWithProblem()
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

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
        return array(
            array(true),
            array(false),
        );
    }
}
