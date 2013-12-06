<?php

use Alchemy\Phrasea\Border\File as BorderFile;
use Symfony\Component\HttpFoundation\Request;

class API_V1_adapterTest extends \PhraseanetAuthenticatedTestCase
{
    /**
     * @var API_V1_adapter
     */
    protected $object;

    public function setUp()
    {
        parent::setUp();
        self::$DI['app']->register(new \API_V1_Timer());
        $this->object = new API_V1_adapter(self::$DI['app']);
    }

    public function testGet_error_code()
    {
        $request = new Request([], [], [], [], [], ['HTTP_Accept' => 'application/json']);
        $result = $this->object->get_error_code($request, 400);
        $this->assertTrue(is_array(json_decode($result->format(), true)));
        $this->assertEquals(400, $result->get_http_code());
        $this->assertEquals('application/json', $result->get_content_type());

        $result = $this->object->get_error_code($request, 403);
        $this->assertTrue(is_array(json_decode($result->format(), true)));
        $this->assertEquals(403, $result->get_http_code());
        $this->assertEquals('application/json', $result->get_content_type());

        $result = $this->object->get_error_code($request, 500);
        $this->assertTrue(is_array(json_decode($result->format(), true)));
        $this->assertEquals(500, $result->get_http_code());
        $this->assertEquals('application/json', $result->get_content_type());

        $result = $this->object->get_error_code($request, 405);
        $this->assertTrue(is_array(json_decode($result->format(), true)));
        $this->assertEquals(405, $result->get_http_code());
        $this->assertEquals('application/json', $result->get_content_type());

        $result = $this->object->get_error_code($request, 404);
        $this->assertTrue(is_array(json_decode($result->format(), true)));
        $this->assertEquals(404, $result->get_http_code());
        $this->assertEquals('application/json', $result->get_content_type());

        $result = $this->object->get_error_code($request, 401);
        $this->assertTrue(is_array(json_decode($result->format(), true)));
        $this->assertEquals(401, $result->get_http_code());
        $this->assertEquals('application/json', $result->get_content_type());
    }

    public function testGet_error_message()
    {
        $request = new Request([], [], [], [], [], ['HTTP_Accept' => 'application/json']);
        $result = $this->object->get_error_message($request, API_V1_result::ERROR_BAD_REQUEST, 'detaillage');
        $this->assertTrue(is_array(json_decode($result->format(), true)));
        $this->assertEquals(400, $result->get_http_code());
        $this->assertEquals('application/json', $result->get_content_type());

        $result = $this->object->get_error_message($request, API_V1_result::ERROR_FORBIDDEN, 'detaillage');
        $this->assertTrue(is_array(json_decode($result->format(), true)));
        $this->assertEquals(403, $result->get_http_code());
        $this->assertEquals('application/json', $result->get_content_type());

        $result = $this->object->get_error_message($request, API_V1_result::ERROR_INTERNALSERVERERROR, 'detaillage');
        $this->assertTrue(is_array(json_decode($result->format(), true)));
        $this->assertEquals(500, $result->get_http_code());
        $this->assertEquals('application/json', $result->get_content_type());

        $result = $this->object->get_error_message($request, API_V1_result::ERROR_METHODNOTALLOWED, 'detaillage');
        $this->assertTrue(is_array(json_decode($result->format(), true)));
        $this->assertEquals(405, $result->get_http_code());
        $this->assertEquals('application/json', $result->get_content_type());

        $result = $this->object->get_error_message($request, API_V1_result::ERROR_NOTFOUND, 'detaillage');
        $this->assertTrue(is_array(json_decode($result->format(), true)));
        $this->assertEquals(404, $result->get_http_code());
        $this->assertEquals('application/json', $result->get_content_type());

        $result = $this->object->get_error_message($request, API_V1_result::ERROR_UNAUTHORIZED, 'detaillage');
        $this->assertTrue(is_array(json_decode($result->format(), true)));
        $this->assertEquals(401, $result->get_http_code());
        $this->assertEquals('application/json', $result->get_content_type());
    }

    public function testGet_version()
    {
        $this->assertEquals('1.3', $this->object->get_version());
    }

    public function testGet_databoxes()
    {
        $request = new Request([], [], [], [], [], ['HTTP_Accept' => 'application/json']);
        $result = $this->object->get_databoxes($request);
        $this->assertEquals(200, $result->get_http_code());
        $this->assertEquals('application/json', $result->get_content_type());
        $this->assertTrue(is_array(json_decode($result->format(), true)));
    }

    public function testGet_databox_collections()
    {
        $request = new Request();
        foreach (self::$DI['app']['phraseanet.appbox']->get_databoxes() as $databox) {
            $result = $this->object->get_databox_collections($request, $databox->get_sbas_id());
            $this->assertEquals(200, $result->get_http_code());
            $this->assertEquals('application/json', $result->get_content_type());
            $this->assertTrue(is_array(json_decode($result->format(), true)));
        }
    }

    public function testGet_record()
    {

        $request = new Request([], [], [], [], [], ['HTTP_Accept' => 'application/json']);
        $result = $this->object->get_record($request, self::$DI['record_1']->get_sbas_id(), "-40");
        $this->assertEquals(400, $result->get_http_code());

        $request = new Request([], [], [], [], [], ['HTTP_Accept' => 'application/json']);
        $result = $this->object->get_record($request, self::$DI['record_1']->get_sbas_id(), self::$DI['record_1']->get_record_id());
        $this->assertEquals(200, $result->get_http_code());
        $this->assertEquals('application/json', $result->get_content_type());
        $this->assertTrue(is_array(json_decode($result->format(), true)));
    }

    public function testGet_databox_status()
    {
        $request = new Request([], [], [], [], [], ['HTTP_Accept' => 'application/json']);
        foreach (self::$DI['app']['phraseanet.appbox']->get_databoxes() as $databox) {
            $result = $this->object->get_databox_status($request, $databox->get_sbas_id());
            $this->assertEquals(200, $result->get_http_code());
            $this->assertEquals('application/json', $result->get_content_type());
            $this->assertTrue(is_array(json_decode($result->format(), true)));
        }
    }

    public function testGet_databox_metadatas()
    {
        $request = new Request([], [], [], [], [], ['HTTP_Accept' => 'application/json']);
        foreach (self::$DI['app']['phraseanet.appbox']->get_databoxes() as $databox) {
            $result = $this->object->get_databox_metadatas($request, $databox->get_sbas_id());
            $this->assertEquals(200, $result->get_http_code());
            $this->assertEquals('application/json', $result->get_content_type());
            $this->assertTrue(is_array(json_decode($result->format(), true)));
        }
    }

    public function testGet_databox_terms()
    {
        $request = new Request([], [], [], [], [], ['HTTP_Accept' => 'application/json']);
        foreach (self::$DI['app']['phraseanet.appbox']->get_databoxes() as $databox) {
            $result = $this->object->get_databox_terms($request, $databox->get_sbas_id());
            $this->assertEquals(200, $result->get_http_code());
            $this->assertEquals('application/json', $result->get_content_type());
            $this->assertTrue(is_array(json_decode($result->format(), true)));
        }
    }

    public function testSearch_recordsWithRecords()
    {
        $this->authenticate(self::$DI['app']);

        $record = \record_adapter::createFromFile(BorderFile::buildFromPathfile(__DIR__ . '/../../../files/cestlafete.jpg', self::$DI['collection'], self::$DI['app']), self::$DI['app']);

        $request = new Request(['record_type' => "image", 'search_type' => 0], [], [], [], [], ['HTTP_Accept' => 'application/json']);
        $result = $this->object->search_records($request);
        $this->assertEquals(200, $result->get_http_code());
        $this->assertEquals('application/json', $result->get_content_type());
        $this->assertTrue(is_array(json_decode($result->format(), true)));

        $data = json_decode($result->format(), true);

        $found = false;
        foreach ($data['response']['results'] as $retRecord) {
            if ($retRecord['record_id'] == $record->get_record_id() && $retRecord['databox_id'] == $record->get_sbas_id()) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            $this->fail('unable to find the record back');
        }
    }

    public function testSearch_withOffset()
    {
        $request = new Request([], [], [], [], [], ['HTTP_Accept' => 'application/json']);
        $result = $this->object->search_records($request);
        $this->assertEquals(200, $result->get_http_code());
        $this->assertEquals('application/json', $result->get_content_type());
        $this->assertTrue(is_array(json_decode($result->format(), true)));

        $data = json_decode($result->format(), true);

        if ($data['response']['total_results'] < 2) {
            $this->markTestSkipped('Not enough data to test');
        }

        $total = $data['response']['total_results'];

        $request = new Request([
            'offset_start' => 0,
            'per_page' => 1,
        ], [], [], [], [], ['HTTP_Accept' => 'application/json']);
        $resultData1 = $this->object->search_records($request);

        $data = json_decode($resultData1->format(), true);

        $this->assertCount(1, $data['response']['results']);
        $result1 = array_pop($data['response']['results']);

        $request = new Request([
            'offset_start' => 1,
            'per_page' => 1,
        ], [], [], [], [], ['HTTP_Accept' => 'application/json']);
        $resultData2 = $this->object->search_records($request);

        $data = json_decode($resultData2->format(), true);

        $this->assertCount(1, $data['response']['results']);
        $result2 = array_pop($data['response']['results']);

        // item at offset #0 is different than offset at item #1
        $this->assertNotEquals($result1['record_id'], $result2['record_id']);

        // last item is last item
        $request = new Request([
            'offset_start' => $total - 1,
            'per_page' => 10,
        ], [], [], [], [], ['HTTP_Accept' => 'application/json']);
        $resultData = $this->object->search_records($request);

        $data = json_decode($resultData->format(), true);

        $this->assertCount(1, $data['response']['results']);
    }

    public function testSearch_recordsWithStories()
    {
        $this->authenticate(self::$DI['app']);

        $story = \record_adapter::createStory(self::$DI['app'], self::$DI['collection']);

        if (!$story->hasChild(self::$DI['record_1'])) {
            $story->appendChild(self::$DI['record_1']);
        }

        $request = new Request(['search_type' => 1], [], [], [], [], ['HTTP_Accept' => 'application/json']);
        $result = $this->object->search_records($request);
        $this->assertEquals(200, $result->get_http_code());
        $this->assertEquals('application/json', $result->get_content_type());
        $this->assertTrue(is_array(json_decode($result->format(), true)));

        $data = json_decode($result->format(), true);

        $found = false;

        foreach ($data['response']['results'] as $retStory) {
            if ($retStory['record_id'] == $story->get_record_id() && $retStory['databox_id'] == $story->get_sbas_id()) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            $this->fail('unable to find the story back');
        }
    }

    public function testSearchWithStories()
    {
        $this->authenticate(self::$DI['app']);

        $story = \record_adapter::createStory(self::$DI['app'], self::$DI['collection']);

        if (!$story->hasChild(self::$DI['record_1'])) {
            $story->appendChild(self::$DI['record_1']);
        }

        $request = new Request(['search_type' => 1], [], [], [], [], ['HTTP_Accept' => 'application/json']);
        $result = $this->object->search($request);
        $this->assertEquals(200, $result->get_http_code());
        $this->assertEquals('application/json', $result->get_content_type());
        $this->assertTrue(is_array(json_decode($result->format(), true)));

        $data = json_decode($result->format(), true);

        $this->assertArrayHasKey('records', $data['response']['results']);
        $this->assertArrayHasKey('stories', $data['response']['results']);

        $found = false;

        foreach ($data['response']['results']['stories'] as $retStory) {
            if ($retStory['story_id'] == $story->get_record_id() && $retStory['databox_id'] == $story->get_sbas_id()) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            $this->fail('unable to find the story back');
        }
    }

    public function testSearchWithRecords()
    {
        $this->authenticate(self::$DI['app']);

        $record = \record_adapter::createFromFile(BorderFile::buildFromPathfile(__DIR__ . '/../../../files/cestlafete.jpg', self::$DI['collection'], self::$DI['app']), self::$DI['app']);

        $request = new Request(['search_type' => 0], [], [], [], [], ['HTTP_Accept' => 'application/json']);
        $result = $this->object->search($request);
        $this->assertEquals(200, $result->get_http_code());
        $this->assertEquals('application/json', $result->get_content_type());
        $this->assertTrue(is_array(json_decode($result->format(), true)));

        $data = json_decode($result->format(), true);

        $this->assertArrayHasKey('records', $data['response']['results']);
        $this->assertArrayHasKey('stories', $data['response']['results']);

        $found = false;

        foreach ($data['response']['results']['records'] as $retRecord) {
            if ($retRecord['record_id'] == $record->get_record_id() && $retRecord['databox_id'] == $record->get_sbas_id()) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            $this->fail('unable to find the record back');
        }
    }

    public function testGet_record_related()
    {

        $basketElement = $this->insertOneBasketElement();
        $basketElement->setRecord(self::$DI['record_1']);

        $story = \record_adapter::createStory(self::$DI['app'], self::$DI['collection']);

        if (!$story->hasChild(self::$DI['record_1'])) {
            $story->appendChild(self::$DI['record_1']);
        }

        self::$DI['app']['EM']->flush($basketElement);

        $request = new Request([], [], [], [], [], ['HTTP_Accept' => 'application/json']);
        $result = $this->object->get_record_related($request, self::$DI['record_1']->get_sbas_id(), self::$DI['record_1']->get_record_id());
        $this->assertEquals(200, $result->get_http_code());
        $this->assertEquals('application/json', $result->get_content_type());
        $this->assertTrue(is_array(json_decode($result->format(), true)));

        $data = json_decode($result->format(), true);

        $this->assertArrayHasKey('baskets', $data['response']);
        $this->assertArrayHasKey('stories', $data['response']);

        $found = false;
        foreach ($data['response']['baskets'] as $bask) {
            if ($bask['basket_id'] == $basketElement->getBasket()->getId()) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            $this->fail('unable to find the basket back');
        }

        $found = false;
        foreach ($data['response']['stories'] as $retStory) {
            if ($retStory['story_id'] == $story->get_record_id() && $retStory['databox_id'] == $story->get_sbas_id()) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            $this->fail('unable to find the story back');
        }
    }

    public function testGet_record_metadatas()
    {

        $request = new Request([], [], [], [], [], ['HTTP_Accept' => 'application/json']);
        $result = $this->object->get_record_metadatas($request, self::$DI['record_1']->get_sbas_id(), self::$DI['record_1']->get_record_id());
        $this->assertEquals(200, $result->get_http_code());
        $this->assertEquals('application/json', $result->get_content_type());
        $this->assertTrue(is_array(json_decode($result->format(), true)));
    }

    public function testGet_record_status()
    {

        $request = new Request();
        $request = new Request([], [], [], [], [], ['HTTP_Accept' => 'application/json']);
        $result = $this->object->get_record_status($request, self::$DI['record_1']->get_sbas_id(), self::$DI['record_1']->get_record_id());
        $this->assertEquals(200, $result->get_http_code());
        $this->assertEquals('application/json', $result->get_content_type());
        $this->assertTrue(is_array(json_decode($result->format(), true)));
    }

    public function testGet_record_embed()
    {

        $request = new Request([], [], [], [], [], ['HTTP_Accept' => 'application/json']);
        $result = $this->object->get_record_embed($request, self::$DI['record_1']->get_sbas_id(), self::$DI['record_1']->get_record_id());
        $this->assertEquals(200, $result->get_http_code());
        $this->assertEquals('application/json', $result->get_content_type());
        $this->assertTrue(is_array(json_decode($result->format(), true)));
    }

    public function testSet_record_metadatas()
    {
        $databox = self::$DI['record_1']->get_databox();
        $request = new Request(["salut" => "salut c'est la fete"], [], [], [], [], ['HTTP_Accept' => 'application/json']);
        $result = $this->object->set_record_metadatas($request, self::$DI['record_1']->get_sbas_id(), self::$DI['record_1']->get_record_id());
        $this->assertEquals(400, $result->get_http_code());

        $request = new Request(["metadatas" => "salut c'est la fete"], [], [], [], [], ['HTTP_Accept' => 'application/json']);
        $this->object->set_record_metadatas($request, self::$DI['record_1']->get_sbas_id(), self::$DI['record_1']->get_record_id());
        $this->assertEquals(400, $result->get_http_code());

        if (sizeof(self::$DI['record_1']->get_caption()->get_fields()) == 0) {
            $caption_field_value = caption_Field_Value::create(self::$DI['app'], databox_field::get_instance(self::$DI['app'], $databox, 1), self::$DI['record_1'], 'my value');
        }

        $metadatas = [];

        foreach (self::$DI['record_1']->get_databox()->get_meta_structure()->get_elements() as $field) {
            try {
                $values = self::$DI['record_1']->get_caption()->get_field($field->get_name())->get_values();
                $value = array_pop($values);
                $meta_id = $value->getId();
            } catch (\Exception $e) {
                $meta_id = null;
            }

            $metadatas[] = [
                'meta_id'        => $meta_id
                , 'meta_struct_id' => $field->get_id()
                , 'value'          => 'poOM POOM TCHOK ' . $field->get_id()
            ];
        }

        $request = new Request(["metadatas" => $metadatas], [], [], [], [], ['HTTP_Accept' => 'application/json']);

        $result = $this->object->set_record_metadatas($request, self::$DI['record_1']->get_sbas_id(), self::$DI['record_1']->get_record_id());

        $response = json_decode($result->format(), true);

        $this->assertEquals($response['meta']['http_code'], 200);

        $this->checkResponseField($result, "record_metadatas", 'array');
    }

    public function testSet_record_status()
    {
        $app = self::$DI['app'];
        $stub = $this->getMock("API_V1_adapter", ["list_record_status"], [$app]);
        $databox = self::$DI['record_1']->get_databox();

        $statusbit = null;
        foreach ($databox->get_statusbits() as $key => $value) {
            $statusbit = $key;
            break;
        }

        if (null === $statusbit) {
            $this->markTestSkipped('No status bit defined in databox');
        }

        $request = new Request(["salut" => "salut c'est la fete"], [], [], [], [], ['HTTP_Accept' => 'application/json']);
        $result = $stub->set_record_status($request, self::$DI['record_1']->get_sbas_id(), self::$DI['record_1']->get_record_id());
        $this->assertEquals(400, $result->get_http_code());

        $request = new Request(["status" => "salut c'est la fete"], [], [], [], [], ['HTTP_Accept' => 'application/json']);
        $stub->set_record_status($request, self::$DI['record_1']->get_sbas_id(), self::$DI['record_1']->get_record_id());
        $this->assertEquals(400, $result->get_http_code());

        $status = [$statusbit => '1'];

        $request = new Request(["status" => $status], [], [], [], [], ['HTTP_Accept' => 'application/json']);
        //check method use record->get_caption
        $stub->expects($this->once())
            ->method("list_record_status")
            ->will($this->returnValue(new stdClass()));
        //check for metadas fiels in response
        $result = $stub->set_record_status($request, self::$DI['record_1']->get_sbas_id(), self::$DI['record_1']->get_record_id());
        $this->checkResponseField($result, "status", 'array');
        }

    public function testSet_record_collection()
    {
        $app = self::$DI['app'];
        $stub = $this->getMock("API_V1_adapter", ["list_record"], [$app]);
        $databox = self::$DI['record_1']->get_databox();

        $request = new Request(["salut" => "salut c'est la fete"], [], [], [], [], ['HTTP_Accept' => 'application/json']);
        $result = $stub->set_record_collection($request, self::$DI['record_1']->get_sbas_id(), self::$DI['record_1']->get_record_id());
        $this->assertEquals(400, $result->get_http_code());

        foreach ($app['phraseanet.appbox']->get_databoxes() as $databox) {
            $collections = $databox->get_collections();
            break;
        }

        $collection = array_shift($collections);

        $request = new Request(["base_id" => $collection->get_base_id()], [], [], [], [], ['HTTP_Accept' => 'application/json']);
        //check method use record->get_caption
        $stub->expects($this->once())
            ->method("list_record")
            ->will($this->returnValue(new stdClass()));
        //check for metadas fiels in response
        $result = $stub->set_record_collection($request, self::$DI['record_1']->get_sbas_id(), self::$DI['record_1']->get_record_id());
        $this->checkResponseField($result, "record", 'array');
    }

    /**
     * @todo Implement testAdd_record_tobasket().
     */
    public function testAdd_record_tobasket()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testSearch_baskets()
    {
        $request = new Request([], [], [], [], [], ['HTTP_Accept' => 'application/json']);
        $result = $this->object->search_baskets($request);
        $this->assertEquals(200, $result->get_http_code());
        $this->assertEquals('application/json', $result->get_content_type());
        $this->assertTrue(is_array(json_decode($result->format(), true)));
    }

    public function testCreate_basket()
    {
        $request = new Request([], [], ['name' => 'BIG BASKET'], [], [], ['HTTP_Accept' => 'application/json']);
        $result = $this->object->create_basket($request);
        $this->assertEquals(200, $result->get_http_code());
        $this->assertEquals('application/json', $result->get_content_type());
        $this->assertTrue(is_array(json_decode($result->format(), true)));

        $n = 0;
        $response = json_decode($result->format(), true);
        $this->assertArrayHasKey('basket', $response['response']);

        $em = self::$DI['app']['EM'];

        $basket = self::$DI['app']['converter.basket']->convert($response['response']['basket']['basket_id']);
        self::$DI['app']['acl.basket']->isOwner($basket, self::$DI['app']['authentication']->getUser());

        $em->remove($basket);
        $em->flush();
    }

    public function testDelete_basket()
    {
        $usr_id = self::$DI['app']['authentication']->getUser()->get_id();
        $user = User_Adapter::getInstance($usr_id, self::$DI['app']);

        $em = self::$DI['app']['EM'];

        $Basket = new Alchemy\Phrasea\Model\Entities\Basket();
        $Basket->setName('Delete test');
        $Basket->setOwner($user);

        $em->persist($Basket);
        $em->flush();

        $request = new Request([], [], [], [], [], ['HTTP_Accept' => 'application/json']);
        $result = $this->object->delete_basket($request, $Basket);
        $this->assertEquals(200, $result->get_http_code());
        $this->assertEquals('application/json', $result->get_content_type());
        $this->assertTrue(is_array(json_decode($result->format(), true)));

        try {
            $basket = self::$DI['app']['converter.basket']->convert($Basket->getId());
            self::$DI['app']['acl.basket']->isOwner($basket, $user);
            $this->fail('An exception should have been raised');
        } catch (\Exception $e) {

        }
    }

    public function testGet_basket()
    {
        $usr_id = self::$DI['app']['authentication']->getUser()->get_id();

        $basket = $this->insertOneBasket();

        $request = new Request([], [], [], [], [], ['HTTP_Accept' => 'application/json']);
        $result = $this->object->get_basket($request, $basket);
        $this->assertEquals(200, $result->get_http_code());
        $this->assertEquals('application/json', $result->get_content_type());
        $this->assertTrue(is_array(json_decode($result->format(), true)));
    }

    public function testSet_basket_title()
    {
        $usr_id = self::$DI['app']['authentication']->getUser()->get_id();

        $basket = $this->insertOneBasket();

        $request = new Request([], [], ['name' => 'PROUTO'], [], [], ['HTTP_Accept' => 'application/json']);
        $result = $this->object->set_basket_title($request, $basket);
        $this->assertEquals(200, $result->get_http_code());
        $this->assertEquals('application/json', $result->get_content_type());
        $this->assertTrue(is_array(json_decode($result->format(), true)));

        $repository = self::$DI['app']['EM']->getRepository('\Alchemy\Phrasea\Model\Entities\Basket');

        $ret_bask = $repository->find($basket->getId());

        $this->assertEquals('PROUTO', $ret_bask->getName());
    }

    public function testSet_basket_description()
    {
        $usr_id = self::$DI['app']['authentication']->getUser()->get_id();

        $basket = $this->insertOneBasket();

        $request = new Request([], [], ['description' => 'une belle description'], [], [], ['HTTP_Accept' => 'application/json']);
        $result = $this->object->set_basket_description($request, $basket);
        $this->assertEquals(200, $result->get_http_code());
        $this->assertEquals('application/json', $result->get_content_type());
        $this->assertTrue(is_array(json_decode($result->format(), true)));

        $repository = self::$DI['app']['EM']->getRepository('\Alchemy\Phrasea\Model\Entities\Basket');

        $ret_bask = $repository->find($basket->getId());

        $this->assertEquals('une belle description', $ret_bask->getDescription());
    }

    public function testSearch_publications()
    {
        $request = new Request([], [], [], [], [], ['HTTP_Accept' => 'application/json']);
        $feed = $this->insertOneFeed(self::$DI['user']);
        $result = $this->object->search_publications($request, self::$DI['user']);
        $this->checkResponseField($result, "feeds", 'array');
    }

    public function testRemove_publications()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testGet_publication()
    {
        self::$DI['app']['notification.deliverer'] = $this->getMockBuilder('Alchemy\Phrasea\Notification\Deliverer')
            ->disableOriginalConstructor()
            ->getMock();

        $date = new DateTime();
        $request = new Request([], [], [], [], [], ['HTTP_Accept'    => 'application/json']);
        $feedItem = $this->insertOneFeedItem(self::$DI['user']);
        $feed = $feedItem->getEntry()->getFeed();

        $feeds = self::$DI['app']['EM']->getRepository('Alchemy\Phrasea\Model\Entities\Feed')->getAllForUser(self::$DI['app']['acl']->get(self::$DI['user']));
        foreach ($feeds as $feed) {
            $result = $this->object->get_publication($request, $feed->getId(), self::$DI['user']);
            $this->checkResponseField($result, "feed", 'array');
            $this->checkResponseField($result, "entries", 'array');
            $this->checkResponseField($result, "offset_start", 'integer');
            $this->checkResponseField($result, "per_page", 'integer');
        }
    }

    private function checkResponseField(API_V1_result $result, $field, $type)
    {
        $response = json_decode($result->format(), true);
        $this->assertArrayHasKey($field, $response['response']);
        $this->assertInternalType($type, $response['response'][$field]);
    }
}
