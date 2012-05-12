<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class ControllerUsrListsTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    public function createApplication()
    {
        return require __DIR__ . '/../../../../../lib/Alchemy/Phrasea/Application/Prod.php';
    }

    public function setUp()
    {
        parent::setUp();
        $this->client = $this->createClient();
    }

    /**
     * Default route test
     */
    public function testRouteSlash()
    {
        $entry1 = $this->insertOneUsrListEntry(self::$user, self::$user);
        $entry2 = $this->insertOneUsrListEntry(self::$user, self::$user_alt1);
        $entry3 = $this->insertOneUsrListEntry(self::$user, self::$user);
        $entry4 = $this->insertOneUsrListEntry(self::$user, self::$user_alt1);
        $entry5 = $this->insertOneUsrListEntry(self::$user_alt1, self::$user_alt1);
        $entry6 = $this->insertOneUsrListEntry(self::$user_alt1, self::$user_alt2);

        $route = '/lists/all/';

        $this->client->request('GET', $route, array(), array(), array("HTTP_CONTENT_TYPE" => "application/json", "HTTP_ACCEPT"       => "application/json"));

        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());

        $datas = (array) json_decode($response->getContent());

        $this->assertEquals(4, count($datas['result']));
    }

    protected function checkList($list, $owners = true, $users = true)
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
        $route = '/lists/list/';

        $this->client->request('POST', $route);

        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());

        $datas = (array) json_decode($response->getContent());

        $this->assertArrayHasKey('success', $datas);
        $this->assertArrayHasKey('message', $datas);

        $this->assertFalse($datas['success']);

        $this->client->request('POST', $route, array('name' => 'New List'));

        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());

        $datas = (array) json_decode($response->getContent());

        $this->assertArrayHasKey('success', $datas);
        $this->assertArrayHasKey('message', $datas);

        $this->assertTrue($datas['success']);
    }

    public function testGetList()
    {
        $entry = $this->insertOneUsrListEntry(self::$user, self::$user_alt1);
        $list_id = $entry->getList()->getId();

        $route = '/lists/list/' . $list_id . '/';

        $this->client->request('GET', $route, array(), array(), array("HTTP_CONTENT_TYPE" => "application/json", "HTTP_ACCEPT"       => "application/json"));

        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());

//    $datas = (array) json_decode($response->getContent());
//
//    $this->assertTrue(is_array($datas));
//    $this->assertArrayhasKey('result', $datas);
//    $this->checkList($datas['result']);
    }

    public function testPostUpdate()
    {
        $entry = $this->insertOneUsrListEntry(self::$user, self::$user_alt1);
        $list_id = $entry->getList()->getId();

        $route = '/lists/list/' . $list_id . '/update/';

        $this->client->request('POST', $route);

        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());

        $datas = (array) json_decode($response->getContent());

        $this->assertArrayHasKey('success', $datas);
        $this->assertArrayHasKey('message', $datas);

        $this->assertFalse($datas['success']);


        $this->client->request('POST', $route, array('name' => 'New NAME'));

        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());

        $datas = (array) json_decode($response->getContent());

        $this->assertArrayHasKey('success', $datas);
        $this->assertArrayHasKey('message', $datas);

        $this->assertTrue($datas['success']);
    }

    public function testPostDelete()
    {
        $entry = $this->insertOneUsrListEntry(self::$user, self::$user_alt1);
        $list_id = $entry->getList()->getId();

        $route = '/lists/list/' . $list_id . '/delete/';

        $this->client->request('POST', $route);

        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());

        $datas = (array) json_decode($response->getContent());

        $this->assertArrayHasKey('success', $datas);
        $this->assertArrayHasKey('message', $datas);

        $this->assertTrue($datas['success']);

        $repository = self::$core->getEntityManager()->getRepository('Entities\UsrList');

        $this->assertNull($repository->find($list_id));
    }

    public function testPostRemoveEntry()
    {
        $entry = $this->insertOneUsrListEntry(self::$user, self::$user_alt1);
        $list_id = $entry->getList()->getId();
        $usr_id = $entry->getUser()->get_id();
        $entry_id = $entry->getId();

        $route = '/lists/list/' . $list_id . '/remove/' . $usr_id . '/';

        $this->client->request('POST', $route, array(), array(), array("HTTP_CONTENT_TYPE" => "application/json", "HTTP_ACCEPT"       => "application/json"));

        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());

        $datas = (array) json_decode($response->getContent());

        $this->assertArrayHasKey('success', $datas);
        $this->assertArrayHasKey('message', $datas);

        $this->assertTrue($datas['success']);

        $repository = self::$core->getEntityManager()->getRepository('Entities\UsrListEntry');

        $this->assertNull($repository->find($entry_id));
    }

    public function testPostAddEntry()
    {
        $list = $this->insertOneUsrList(self::$user);

        $this->assertEquals(0, $list->getEntries()->count());

        $route = '/lists/list/' . $list->getId() . '/add/';

        $this->client->request('POST', $route, array('usr_ids' => array(self::$user->get_id())), array(), array("HTTP_CONTENT_TYPE" => "application/json", "HTTP_ACCEPT"       => "application/json"));

        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());

        $datas = (array) json_decode($response->getContent());

        $this->assertArrayHasKey('success', $datas);
        $this->assertArrayHasKey('message', $datas);

        $this->assertTrue($datas['success']);

        $repository = self::$core->getEntityManager()->getRepository('Entities\UsrList');

        $list = $repository->find($list->getId());

        $this->assertEquals(1, $list->getEntries()->count());
    }

    public function testPostShareList()
    {
        $list = $this->insertOneUsrList(self::$user);

        $this->assertEquals(1, $list->getOwners()->count());

        $route = '/lists/list/' . $list->getId() . '/share/' . self::$user_alt1->get_id() . '/';

        $this->client->request('POST', $route);

        $response = $this->client->getResponse();

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());


        $route = '/lists/list/' . $list->getId() . '/share/' . self::$user_alt1->get_id() . '/';

        $this->client->request('POST', $route, array('role' => 'general'));

        $response = $this->client->getResponse();

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());



        $route = '/lists/list/' . $list->getId() . '/share/' . self::$user_alt1->get_id() . '/';

        $this->client->request('POST', $route, array('role' => \Entities\UsrListOwner::ROLE_ADMIN));

        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());


        $datas = (array) json_decode($response->getContent());

        $this->assertArrayHasKey('success', $datas);
        $this->assertArrayHasKey('message', $datas);

        $this->assertTrue($datas['success']);

        $repository = self::$core->getEntityManager()->getRepository('Entities\UsrList');

        $list = $repository->find($list->getId());

        $this->assertEquals(2, $list->getOwners()->count());
    }

    public function testPostUnShareList()
    {

        $list = $this->insertOneUsrList(self::$user);

        $this->assertEquals(1, $list->getOwners()->count());

        $route = '/lists/list/' . $list->getId() . '/share/' . self::$user_alt1->get_id() . '/';

        $this->client->request('POST', $route, array('role' => \Entities\UsrListOwner::ROLE_ADMIN));

        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());


        $datas = (array) json_decode($response->getContent());

        $this->assertArrayHasKey('success', $datas);
        $this->assertArrayHasKey('message', $datas);

        $this->assertTrue($datas['success']);

        $repository = self::$core->getEntityManager()->getRepository('Entities\UsrList');

        $list = $repository->find($list->getId());

        $this->assertEquals(2, $list->getOwners()->count());



        $route = '/lists/list/' . $list->getId() . '/unshare/' . self::$user_alt1->get_id() . '/';

        $this->client->request('POST', $route);

        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());

        $datas = (array) json_decode($response->getContent());

        $this->assertTrue($datas['success']);

        $repository = self::$core->getEntityManager()->getRepository('Entities\UsrList');

        $list = $repository->find($list->getId());

        self::$core->getEntityManager()->refresh($list);

        $this->assertEquals(1, $list->getOwners()->count());
    }

    public function testPostUnShareFail()
    {

        $list = $this->insertOneUsrList(self::$user);

        $this->assertEquals(1, $list->getOwners()->count());

        $route = '/lists/list/' . $list->getId() . '/share/' . self::$user_alt1->get_id() . '/';

        $this->client->request('POST', $route, array('role' => \Entities\UsrListOwner::ROLE_ADMIN));

        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());


        $datas = (array) json_decode($response->getContent());

        $this->assertArrayHasKey('success', $datas);
        $this->assertArrayHasKey('message', $datas);

        $this->assertTrue($datas['success']);



        $route = '/lists/list/' . $list->getId() . '/share/' . self::$user->get_id() . '/';

        $this->client->request('POST', $route, array('role' => \Entities\UsrListOwner::ROLE_USER));

        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());


        $datas = (array) json_decode($response->getContent());

        $this->assertArrayHasKey('success', $datas);
        $this->assertArrayHasKey('message', $datas);

        $this->assertFalse($datas['success']);



        $route = '/lists/list/' . $list->getId() . '/share/' . self::$user_alt1->get_id() . '/';

        $this->client->request('POST', $route, array('role' => \Entities\UsrListOwner::ROLE_USER));

        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());


        $datas = (array) json_decode($response->getContent());

        $this->assertArrayHasKey('success', $datas);
        $this->assertArrayHasKey('message', $datas);

        $this->assertTrue($datas['success']);



        $repository = self::$core->getEntityManager()->getRepository('Entities\UsrList');

        $list = $repository->find($list->getId());

        $this->assertEquals(2, $list->getOwners()->count());



        $route = '/lists/list/' . $list->getId() . '/unshare/' . self::$user_alt1->get_id() . '/';

        $this->client->request('POST', $route);

        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());

        $datas = (array) json_decode($response->getContent());

        $this->assertTrue($datas['success']);
    }

    protected function checkOwner($owner)
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

    protected function checkUser($user)
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
