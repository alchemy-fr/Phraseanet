<?php

namespace Alchemy\Tests\Phrasea\Model\Manipulator;

use Alchemy\Phrasea\Model\Manipulator\ApiLogManipulator;
use Alchemy\Phrasea\Model\Manipulator\ApiAccountManipulator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiLogManipulatorTest extends \PhraseanetTestCase
{
    public function testCreate()
    {
        $manipulator = new ApiAccountManipulator(self::$DI['app']['orm.em'], self::$DI['app']['repo.api-accounts']);
        $account = $manipulator->create(self::$DI['oauth2-app-user'], self::$DI['user']);
        $nbLogs = count(self::$DI['app']['repo.api-logs']->findAll());
        $manipulator = new ApiLogManipulator(self::$DI['app']['orm.em'], self::$DI['app']['repo.api-logs']);
        $manipulator->create($account, Request::create('/databoxes/list/', 'POST'), new Response());
        $this->assertGreaterThan($nbLogs, count(self::$DI['app']['repo.api-accounts']->findAll()));
    }

    /**
     * @dataProvider apiRouteProvider
     */
    public function testsLogHydration($path, $expected)
    {
        $emMock  = $this->getMock('\Doctrine\ORM\EntityManager', ['persist', 'flush'], [], '', false);
        $account = $this->getMock('\Alchemy\Phrasea\Model\Entities\ApiAccount');
        $manipulator = new ApiLogManipulator($emMock, self::$DI['app']['repo.api-logs']);
        $log = $manipulator->create($account, Request::create($path, 'POST'), new Response());
        $this->assertEquals($expected['resource'], $log->getResource());
        $this->assertEquals($expected['general'], $log->getGeneral());
        $this->assertEquals($expected['aspect'], $log->getAspect());
        $this->assertEquals($expected['action'], $log->getAction());
    }

    public function testDelete()
    {
        $manipulator = new ApiAccountManipulator(self::$DI['app']['orm.em'], self::$DI['app']['repo.api-accounts']);
        $account = $manipulator->create(self::$DI['oauth2-app-user'], self::$DI['user']);
        $manipulator = new ApiLogManipulator(self::$DI['app']['orm.em'], self::$DI['app']['repo.api-logs']);
        $log = $manipulator->create($account, Request::create('/databoxes/list/', 'POST'), new Response());
        $countBefore = count(self::$DI['app']['repo.api-logs']->findAll());
        $manipulator->delete($log);
        $this->assertGreaterThan(count(self::$DI['app']['repo.api-logs']->findAll()), $countBefore);
    }

    public function testUpdate()
    {
        $manipulator = new ApiAccountManipulator(self::$DI['app']['orm.em'], self::$DI['app']['repo.api-accounts']);
        $account = $manipulator->create(self::$DI['oauth2-app-user'], self::$DI['user']);
        $manipulator = new ApiLogManipulator(self::$DI['app']['orm.em'], self::$DI['app']['repo.api-logs']);
        $log = $manipulator->create($account, Request::create('/databoxes/list/', 'POST'), new Response());
        $log->setAspect('a-new-aspect');
        $manipulator->update($log);
        $log =  self::$DI['app']['repo.api-logs']->find($log->getId());
        $this->assertEquals('a-new-aspect', $log->getAspect());
    }

    public function apiRouteProvider()
    {
        return [
            ['/databoxes/list/', ['resource' => 'databoxes', 'aspect' => null, 'action' => 'list', 'general' => 'databoxes']],
            ['/databoxes/1/collections/', ['resource' => 'databoxes', 'aspect' => 'collections', 'action' => null, 'general' => 'databoxes']],
            ['/databoxes/1/status/', ['resource' => 'databoxes', 'aspect' => 'status', 'action' => null, 'general' => 'databoxes']],
            ['/databoxes/1/metadatas/', ['resource' => 'databoxes', 'aspect' => 'metadatas', 'action' => null, 'general' => 'databoxes']],
            ['/databoxes/1/termsOfUse/', ['resource' => 'databoxes', 'aspect' => 'termsOfUse', 'action' => null, 'general' => 'databoxes']],
            ['/quarantine/list/', ['resource' => 'quarantine', 'aspect' => null, 'action' => 'list', 'general' => 'quarantine']],
            ['/records/add/', ['resource' => 'records', 'aspect' => null, 'action' => 'add', 'general' => 'records']],
            ['/records/search/', ['resource' => 'records', 'aspect' => null, 'action' => 'search', 'general' => 'records']],
            ['/records/1/1/caption/', ['resource' => 'records', 'aspect' => 'caption', 'action' => null, 'general' => 'records']],
            ['/records/1/1/metadatas/', ['resource' => 'records', 'aspect' => 'metadatas', 'action' => null, 'general' => 'records']],
            ['/records/1/1/status/', ['resource' => 'records', 'aspect' => 'status', 'action' => null, 'general' => 'records']],
            ['/records/1/1/embed/', ['resource' => 'records', 'aspect' => 'embed', 'action' => null, 'general' => 'records']],
            ['/records/1/1/related/', ['resource' => 'records', 'aspect' => 'related', 'action' => null, 'general' => 'records']],
            ['/records/1/1/', ['resource' => 'records', 'aspect' => null, 'action' => 'get', 'general' => 'records']],
            ['/records/1/1/setstatus/', ['resource' => 'records', 'aspect' => null, 'action' => 'setstatus', 'general' => 'records']],
            ['/records/1/1/setmetadatas/', ['resource' => 'records', 'aspect' => null, 'action' => 'setmetadatas', 'general' => 'records']],
            ['/records/1/1/setcollection/', ['resource' => 'records', 'aspect' => null, 'action' => 'setcollection', 'general' => 'records']],
            ['/stories/1/1/embed/', ['resource' => 'stories', 'aspect' => 'embed', 'action' => null, 'general' => 'stories']],
            ['/stories/1/1/', ['resource' => 'stories', 'aspect' => null, 'action' => 'get', 'general' => 'stories']],
            ['/baskets/add/', ['resource' => 'baskets', 'aspect' => null, 'action' => 'add', 'general' => 'baskets']],
            ['/baskets/1/content/', ['resource' => 'baskets', 'aspect' => 'content', 'action' => '', 'general' => 'baskets']],
            ['/baskets/1/delete/', ['resource' => 'baskets', 'aspect' => null, 'action' => 'delete', 'general' => 'baskets']],
            ['/baskets/1/setdescription/', ['resource' => 'baskets', 'aspect' => null, 'action' => 'setdescription', 'general' => 'baskets']],
            ['/baskets/1/setname/', ['resource' => 'baskets', 'aspect' => null, 'action' => 'setname', 'general' => 'baskets']],
            ['/feeds/list/', ['resource' => 'feeds', 'aspect' => null, 'action' => 'list', 'general' => 'feeds']],
            ['/feeds/1/content/', ['resource' => 'feeds', 'aspect' => 'content', 'action' => null, 'general' => 'feeds']],
            ['/feeds/content/', ['resource' => 'feeds', 'aspect' => 'content', 'action' => null, 'general' => 'feeds']],
            ['/feeds/entry/1/', ['resource' => 'feeds', 'aspect' => 'entry', 'action' => null, 'general' => 'feeds']],
            ['/monitor/phraseanet/', ['resource' => null, 'aspect' => 'phraseanet', 'action' => null, 'general' => 'monitor']],
            ['/monitor/scheduler/', ['resource' => null, 'aspect' => 'scheduler', 'action' => null, 'general' => 'monitor']],
            ['/monitor/task/1/', ['resource' => null, 'aspect' => 'task', 'action' => 'get', 'general' => 'monitor']],
            ['/monitor/task/1/stop/', ['resource' => null, 'aspect' => 'task', 'action' => 'stop', 'general' => 'monitor']],
            ['/monitor/task/1/start/', ['resource' => null, 'aspect' => 'task', 'action' => 'start', 'general' => 'monitor']],
            ['/monitor/tasks/', ['resource' => null, 'aspect' => 'tasks', 'action' => null, 'general' => 'monitor']],
        ];
    }
}
