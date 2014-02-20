<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

class UsrListsTest extends \PhraseanetAuthenticatedWebTestCase
{
    protected $client;

    /**
     * Default route test
     */
    public function testRouteSlash()
    {
        $route = '/prod/lists/all/';

        self::$DI['client']->request('GET', $route, [], [], ["HTTP_CONTENT_TYPE" => "application/json", "HTTP_ACCEPT"       => "application/json"]);

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());

        $datas = (array) json_decode($response->getContent());

        $this->assertEquals(1, count($datas['result']));
    }

    private function checkList($list, $owners = true, $users = true)
    {
        $this->assertInstanceOf('stdClass', $list);
        $this->assertObjectHasAttribute('name', $list);
        $this->assertObjectHasAttribute('created', $list);
        $this->assertObjectHasAttribute('updated', $list);
        $this->assertObjectHasAttribute('owners', $list);
        $this->assertObjectHasAttribute('users', $list);

        if ($owners)
            $this->assertTrue(count($list->owners) > 0);

        foreach ($list->owners as $owner) {
            $this->checkOwner($owner);
        }

        if ($users)
            $this->assertTrue(count($list->users) > 0);

        foreach ($list->users as $user) {
            $this->checkUser($user);
        }
    }

    public function testPostList()
    {
        $route = '/prod/lists/list/';

        self::$DI['client']->request('POST', $route);

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());

        $datas = (array) json_decode($response->getContent());

        $this->assertArrayHasKey('success', $datas);
        $this->assertArrayHasKey('message', $datas);

        $this->assertFalse($datas['success']);

        self::$DI['client']->request('POST', $route, ['name' => 'New List']);

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());

        $datas = (array) json_decode($response->getContent());

        $this->assertArrayHasKey('success', $datas);
        $this->assertArrayHasKey('message', $datas);

        $this->assertTrue($datas['success']);
    }

    public function testGetList()
    {
        $entry = self::$DI['app']['EM']->find('Phraseanet:UsrListEntry', 2);
        $list_id = $entry->getList()->getId();

        $route = '/prod/lists/list/' . $list_id . '/';

        self::$DI['client']->request('GET', $route, [], [], ["HTTP_CONTENT_TYPE" => "application/json", "HTTP_ACCEPT"       => "application/json"]);

        $response = self::$DI['client']->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());

        $datas = (array) json_decode($response->getContent());

        $this->assertTrue(is_array($datas));
        $this->assertArrayhasKey('result', $datas);
        $this->checkList($datas['result']);
    }

    public function testPostUpdate()
    {
        $entry = self::$DI['app']['EM']->find('Phraseanet:UsrListEntry', 2);
        $list_id = $entry->getList()->getId();

        $route = '/prod/lists/list/' . $list_id . '/update/';

        self::$DI['client']->request('POST', $route);

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());

        $datas = (array) json_decode($response->getContent());

        $this->assertArrayHasKey('success', $datas);
        $this->assertArrayHasKey('message', $datas);

        $this->assertFalse($datas['success']);

        self::$DI['client']->request('POST', $route, ['name' => 'New NAME']);

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());

        $datas = (array) json_decode($response->getContent());

        $this->assertArrayHasKey('success', $datas);
        $this->assertArrayHasKey('message', $datas);

        $this->assertTrue($datas['success']);
    }

    public function testPostDelete()
    {
        $entry = self::$DI['app']['EM']->find('Phraseanet:UsrListEntry', 2);
        $list_id = $entry->getList()->getId();

        $route = '/prod/lists/list/' . $list_id . '/delete/';

        self::$DI['client']->request('POST', $route);

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());

        $datas = (array) json_decode($response->getContent());

        $this->assertArrayHasKey('success', $datas);
        $this->assertArrayHasKey('message', $datas);

        $this->assertTrue($datas['success']);

        $repository = self::$DI['app']['EM']->getRepository('Phraseanet:UsrList');

        $this->assertNull($repository->find($list_id));
    }

    public function testPostRemoveEntry()
    {
        $entry = self::$DI['app']['EM']->find('Phraseanet:UsrListEntry', 2);
        $list_id = $entry->getList()->getId();
        $usr_id = $entry->getUser(self::$DI['app'])->getId();
        $entry_id = $entry->getId();

        $route = '/prod/lists/list/' . $list_id . '/remove/' . $usr_id . '/';

        self::$DI['client']->request('POST', $route, [], [], ["HTTP_CONTENT_TYPE" => "application/json", "HTTP_ACCEPT"       => "application/json"]);

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());

        $datas = (array) json_decode($response->getContent());

        $this->assertArrayHasKey('success', $datas);
        $this->assertArrayHasKey('message', $datas);

        $this->assertTrue($datas['success']);

        $repository = self::$DI['app']['EM']->getRepository('Phraseanet:UsrListEntry');

        $this->assertNull($repository->find($entry_id));
    }

    public function testPostAddEntry()
    {
        $list = self::$DI['app']['EM']->find('Phraseanet:UsrList', 1);

        $this->assertEquals(2, $list->getEntries()->count());

        $route = '/prod/lists/list/' . $list->getId() . '/add/';

        self::$DI['client']->request('POST', $route, ['usr_ids' => [self::$DI['user_alt2']->getId()]], [], ["HTTP_CONTENT_TYPE" => "application/json", "HTTP_ACCEPT"       => "application/json"]);

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());

        $datas = (array) json_decode($response->getContent());

        $this->assertArrayHasKey('success', $datas);
        $this->assertArrayHasKey('message', $datas);

        $this->assertTrue($datas['success']);
        $this->assertEquals(3, $list->getEntries()->count());
    }

    public function testPostShareList()
    {
        $list = self::$DI['app']['EM']->find('Phraseanet:UsrList', 1);

        $this->assertEquals(1, $list->getOwners()->count());

        $route = '/prod/lists/list/' . $list->getId() . '/share/' . self::$DI['user_alt1']->getId() . '/';

        self::$DI['client']->request('POST', $route);

        $response = self::$DI['client']->getResponse();

        $this->assertBadResponse($response);
        $this->assertEquals('UTF-8', $response->getCharset());

        $route = '/prod/lists/list/' . $list->getId() . '/share/' . self::$DI['user_alt1']->getId() . '/';

        self::$DI['client']->request('POST', $route, ['role' => 'general']);

        $response = self::$DI['client']->getResponse();

        $this->assertBadResponse($response);
        $this->assertEquals('UTF-8', $response->getCharset());

        $route = '/prod/lists/list/' . $list->getId() . '/share/' . self::$DI['user_alt1']->getId() . '/';

        self::$DI['client']->request('POST', $route, ['role' => \Alchemy\Phrasea\Model\Entities\UsrListOwner::ROLE_ADMIN]);

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());

        $datas = (array) json_decode($response->getContent());

        $this->assertArrayHasKey('success', $datas);
        $this->assertArrayHasKey('message', $datas);

        $this->assertTrue($datas['success']);

        $repository = self::$DI['app']['EM']->getRepository('Phraseanet:UsrList');

        $list = $repository->find($list->getId());

        $this->assertEquals(2, $list->getOwners()->count());
    }

    public function testPostUnShareList()
    {
        $list = self::$DI['app']['EM']->find('Phraseanet:UsrList', 1);

        $this->assertEquals(1, $list->getOwners()->count());

        $route = '/prod/lists/list/' . $list->getId() . '/share/' . self::$DI['user_alt1']->getId() . '/';

        self::$DI['client']->request('POST', $route, ['role' => \Alchemy\Phrasea\Model\Entities\UsrListOwner::ROLE_ADMIN]);

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());

        $datas = (array) json_decode($response->getContent());

        $this->assertArrayHasKey('success', $datas);
        $this->assertArrayHasKey('message', $datas);

        $this->assertTrue($datas['success']);

        $repository = self::$DI['app']['EM']->getRepository('Phraseanet:UsrList');

        $list = $repository->find($list->getId());

        $this->assertEquals(2, $list->getOwners()->count());

        $route = '/prod/lists/list/' . $list->getId() . '/unshare/' . self::$DI['user_alt1']->getId() . '/';

        self::$DI['client']->request('POST', $route);

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());

        $datas = (array) json_decode($response->getContent());

        $this->assertTrue($datas['success']);

        $repository = self::$DI['app']['EM']->getRepository('Phraseanet:UsrList');

        $list = $repository->find($list->getId());

        self::$DI['app']['EM']->refresh($list);

        $this->assertEquals(1, $list->getOwners()->count());
    }

    public function testPostUnShareFail()
    {
        $list = self::$DI['app']['EM']->find('Phraseanet:UsrList', 1);

        $this->assertEquals(1, $list->getOwners()->count());

        $route = '/prod/lists/list/' . $list->getId() . '/share/' . self::$DI['user_alt1']->getId() . '/';

        self::$DI['client']->request('POST', $route, ['role' => \Alchemy\Phrasea\Model\Entities\UsrListOwner::ROLE_ADMIN]);

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());

        $datas = (array) json_decode($response->getContent());

        $this->assertArrayHasKey('success', $datas);
        $this->assertArrayHasKey('message', $datas);

        $this->assertTrue($datas['success']);

        $route = '/prod/lists/list/' . $list->getId() . '/share/' . self::$DI['user']->getId() . '/';

        self::$DI['client']->request('POST', $route, ['role' => \Alchemy\Phrasea\Model\Entities\UsrListOwner::ROLE_USER]);

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());

        $datas = (array) json_decode($response->getContent());

        $this->assertArrayHasKey('success', $datas);
        $this->assertArrayHasKey('message', $datas);

        $this->assertFalse($datas['success']);

        $route = '/prod/lists/list/' . $list->getId() . '/share/' . self::$DI['user_alt1']->getId() . '/';

        self::$DI['client']->request('POST', $route, ['role' => \Alchemy\Phrasea\Model\Entities\UsrListOwner::ROLE_USER]);

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());

        $datas = (array) json_decode($response->getContent());

        $this->assertArrayHasKey('success', $datas);
        $this->assertArrayHasKey('message', $datas);

        $this->assertTrue($datas['success']);

        $repository = self::$DI['app']['EM']->getRepository('Phraseanet:UsrList');

        $list = $repository->find($list->getId());

        $this->assertEquals(2, $list->getOwners()->count());

        $route = '/prod/lists/list/' . $list->getId() . '/unshare/' . self::$DI['user_alt1']->getId() . '/';

        self::$DI['client']->request('POST', $route);

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());

        $datas = (array) json_decode($response->getContent());

        $this->assertTrue($datas['success']);
    }

    private function checkOwner($owner)
    {
        $this->assertInstanceOf('stdClass', $owner);
        $this->assertObjectHasAttribute('usr_id', $owner);
        $this->assertObjectHasAttribute('display_name', $owner);
        $this->assertObjectHasAttribute('position', $owner);
        $this->assertObjectHasAttribute('job', $owner);
        $this->assertObjectHasAttribute('company', $owner);
        $this->assertObjectHasAttribute('email', $owner);
        $this->assertObjectHasAttribute('role', $owner);
        $this->assertTrue(ctype_digit($owner->role));
    }

    private function checkUser($user)
    {
        $this->assertInstanceOf('stdClass', $user);
        $this->assertObjectHasAttribute('usr_id', $user);
        $this->assertObjectHasAttribute('display_name', $user);
        $this->assertObjectHasAttribute('position', $user);
        $this->assertObjectHasAttribute('job', $user);
        $this->assertObjectHasAttribute('company', $user);
        $this->assertObjectHasAttribute('email', $user);
    }
}
