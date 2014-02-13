<?php

namespace Alchemy\Tests\Phrasea\Websocket\Consumer;

use Alchemy\Phrasea\Websocket\Consumer\ConsumerManager;

class ConsumerManagerTest extends \PhraseanetTestCase
{
    /**
     * @dataProvider provideConsumerManagerData
     */
    public function testCreate($usrId, $rights, $authenticated, $checkedRights, $hasRights)
    {
        $manager = new ConsumerManager();
        $consumer = $manager->create($this->createSessionMock($usrId, $rights));
        $this->assertSame($authenticated, $consumer->isAuthenticated());
        $this->assertSame($hasRights, $consumer->hasRights($checkedRights));
    }

    public function provideConsumerManagerData()
    {
        return [
            [25, ['task-manager'], true, [], true],
            [25, ['task-manager'], true, ['task-manager'], true],
            [null, ['task-manager'], false, ['task-manager', 'neutron'], false],
            [null, ['neutron', 'task-manager'], false, ['task-manager', 'neutron'], true],
            [42, ['neutron', 'task-manager', 'romain'], true, ['task-manager', 'neutron'], true],
        ];
    }

    private function createSessionMock($usrId, $rights)
    {
        $session = $this->getMock('Symfony\Component\HttpFoundation\Session\SessionInterface');
        $session->expects($this->any())
            ->method('has')
            ->will($this->returnCallback(function ($prop) use ($usrId, $rights) {
                switch ($prop) {
                    case 'usr_id':
                        return $usrId !== null;
                    case 'websockets_rights':
                        return $rights !== null;
                }
            }));

        $session->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(function ($prop) use ($usrId, $rights) {
                switch ($prop) {
                    case 'usr_id':
                        return $usrId;
                    case 'websockets_rights':
                        return $rights;
                }
            }));

        return $session;
    }
}
