<?php

namespace Alchemy\Tests\Phrasea\Command;

use Alchemy\Phrasea\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class CommandTest extends \PhraseanetTestCase
{
    /**
     * @var Command
     */
    protected $object;

    public function setUp(): void
    {
        parent::setUp();
        $this->object = new AbstractCommandTester('name');
    }

    public function testGetFormattedDuration()
    {
        $this->assertMatchesRegularExpression('/50 \w+/', $this->object->getFormattedDuration(50));
        $this->assertMatchesRegularExpression('/1(\.|,)2 \w+/', $this->object->getFormattedDuration(70));
    }
}

class AbstractCommandTester extends Command
{
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {

    }
}
