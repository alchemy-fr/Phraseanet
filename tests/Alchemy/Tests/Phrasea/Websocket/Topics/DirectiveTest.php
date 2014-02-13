<?php

namespace Alchemy\Tests\Phrasea\Websocket\Topics;

use Alchemy\Phrasea\Websocket\Topics\Directive;

class DirectiveTest extends \PhraseanetTestCase
{
    public function testGetters()
    {
        $directive = new Directive('http://topic', true, ['neutron']);
        $this->assertSame('http://topic', $directive->getTopic());
        $this->assertTrue($directive->requireAuthentication());
        $this->assertSame(['neutron'], $directive->getRequiredRights());
    }

    /**
     * @dataProvider provideStatisfiedByCombinaisons
     */
    public function testIsSatisfiedBy($authenticationRequired, $requiredRights, $authenticated, $hasRights, $satisfied)
    {
        $consumer = $this->createConsumerMock($authenticated, $hasRights, $requiredRights);
        $directive = new Directive('http://topic', $authenticationRequired, $requiredRights);
        $this->assertEquals($satisfied, $directive->isStatisfiedBy($consumer));
    }

    public function provideStatisfiedByCombinaisons()
    {
        return [
            [true, ['neutron'], true, true, true],
            [true, [], false, true, false],
            [false, ['neutron'], true, false, false],
            [false, ['neutron'], false, false, false],
        ];
    }

    private function createConsumerMock($authenticated, $hasRights, $requiredRights)
    {
        $consumer = $this->getMock('Alchemy\Phrasea\Websocket\Consumer\ConsumerInterface');
        $consumer->expects($this->any())
             ->method('isAuthenticated')
             ->will($this->returnValue($authenticated));
        $consumer->expects($this->any())
             ->method('hasRights')
            ->with($requiredRights)
             ->will($this->returnValue($hasRights));

        return $consumer;
    }
}
