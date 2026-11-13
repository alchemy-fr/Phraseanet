<?php

namespace Alchemy\Tests\Phrasea\Command\Setup;

use Alchemy\Phrasea\Command\Setup\XSendFileMappingGenerator;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class XSendFileMappingGeneratorTest extends \PhraseanetTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('provideVariousOptions')]
    public function testRunWithoutProblems($option)
    {
        $input = $this->createMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->createStub('Symfony\Component\Console\Output\OutputInterface');

        $input
            ->method('getArgument')
            ->with('type')
            ->will($this->returnValue('nginx'));

        $input
            ->method('getOption')
            ->with($this->isString())
            ->will($this->returnValue($option));

        $command = new XSendFileMappingGenerator();

        self::$DI['cli']['monolog'] = function () {
            return $this->createMock('Monolog\Logger');
        };

        $originalConf = self::$DI['cli']['conf'];
        $conf = $this->createMock('Alchemy\Phrasea\Core\Configuration\PropertyAccess');
        $conf
            ->method('get')
            ->will($this->returnCallback(function ($property, $defaultValue = null) use ($originalConf) {
                return $originalConf->get($property, $defaultValue);
            }));

        self::$DI['cli']['conf'] = $conf;

        if ($option) {
            $conf->expects($this->once())
                ->method('set')
                ->with('xsendfile');
        } else {
            $conf->expects($this->never())
                ->method('set');
        }
        $command->setContainer(self::$DI['cli']);

        $this->assertEquals(0, $command->execute($input, $output));
    }

    public function testRunWithProblem()
    {
        $input = $this->createMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->createStub('Symfony\Component\Console\Output\OutputInterface');

        $logger = $this->createMock('Monolog\Logger');
        $logger->expects($this->once())
            ->method('error');

        self::$DI['cli']['monolog'] = function () use ($logger) {
            return $logger;
        };

        $input
            ->method('getArgument')
            ->with('type')
            ->will($this->returnValue(null));

        $command = new XSendFileMappingGenerator();
        $command->setContainer(self::$DI['cli']);
        $this->expectException('Alchemy\Phrasea\Exception\InvalidArgumentException');
        $command->execute($input, $output);
    }

    public static function provideVariousOptions()
    {
        return [
            [true],
            [false],
        ];
    }
}
