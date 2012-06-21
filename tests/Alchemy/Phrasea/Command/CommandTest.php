<?php

namespace Alchemy\Phrasea\Command;

class CommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Command
     */
    protected $object;

    protected function setUp()
    {
        $this->object = new AbstractCommandTester('name');
        $this->logger = new \Monolog\Logger('test');
    }

    /**
     * @covers Alchemy\Phrasea\Command\Command::setLogger
     * @covers Alchemy\Phrasea\Command\Command::getLogger
     */
    public function testSetLogger()
    {
        $this->object->setLogger($this->logger);
        $this->assertEquals($this->logger, $this->object->getLogger());
    }

    /**
     * @covers Alchemy\Phrasea\Command\Command::checkSetup
     */
    public function testCheckSetup()
    {
        $this->object->checkSetup();
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
    public function requireSetup()
    {
        return true;
    }
}
