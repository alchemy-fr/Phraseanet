<?php

namespace Alchemy\Phrasea\Command;

use Alchemy\Phrasea\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Command
     */
    protected $object;

    protected function setUp()
    {
        $this->object = new AbstractCommandTester('name');
    }

    /**
     * @covers Alchemy\Phrasea\Command\Command::getFormattedDuration
     */
    public function testGetFormattedDuration()
    {
        $this->assertRegExp('/50 \w+/', $this->object->getFormattedDuration(50));
        $this->assertRegExp('/1(\.|,)2 \w+/', $this->object->getFormattedDuration(70));
    }
}

class AbstractCommandTester extends Command
{
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {

    }
}
