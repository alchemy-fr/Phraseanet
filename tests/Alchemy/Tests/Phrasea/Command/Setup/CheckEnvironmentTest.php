<?php

namespace Alchemy\Tests\Phrasea\Command\Setup;

use Alchemy\Phrasea\Command\Setup\CheckEnvironment;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class CheckEnvironmentTest extends \PhraseanetTestCase
{
    public function testRunWithoutProblems()
    {
        $input = $this->createStub('Symfony\Component\Console\Input\InputInterface');
        $output = $this->createStub('Symfony\Component\Console\Output\OutputInterface');

        $command = new CheckEnvironment('system:check');
        $command->setContainer(self::$DI['cli']);
        $this->assertLessThan(2, $command->execute($input, $output));
    }
}
