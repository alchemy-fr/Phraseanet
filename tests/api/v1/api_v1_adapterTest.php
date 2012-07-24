<?php

require_once __DIR__ . '/../../PhraseanetPHPUnitAuthenticatedAbstract.class.inc';

use Symfony\Component\HttpFoundation\Request;

class API_V1_adapterTest extends PhraseanetPHPUnitAuthenticatedAbstract
{
    /**
     * @var API_V1_adapter
     */
    protected $object;

    public function setUp()
    {
        parent::setUp();
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $this->object = new API_V1_adapter(FALSE, $appbox, self::$core);
    }

    public function testGet_error_code()
    {
        $request = new Request(array(), array(), array(), array(), array(), array('HTTP_Accept' => 'application/json'));
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
        $request = new Request(array(), array(), array(), array(), array(), array('HTTP_Accept' => 'application/json'));
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
        $this->assertEquals('1.2', $this->object->get_version());
    }

    public function testGet_databoxes()
    {
        $request = new Request(array(), array(), array(), array(), array(), array('HTTP_Accept' => 'application/json'));
        $result = $this->object->get_databoxes($request);
        $this->assertEquals(200, $result->get_http_code());
        $this->assertEquals('application/json', $result->get_content_type());
        $this->assertTrue(is_array(json_decode($result->format(), true)));
    }

    public function testGet_databox_collections()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $request = new Request();
        foreach ($appbox->get_databoxes() as $databox) {
            $result = $this->object->get_databox_collections($request, $databox->get_sbas_id());
            $this->assertEquals(200, $result->get_http_code());
            $this->assertEquals('application/json', $result->get_content_type());
            $this->assertTrue(is_array(json_decode($result->format(), true)));
        }
    }

    public function testGet_record()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());

        $request = new Request(array(), array(), array(), array(), array(), array('HTTP_Accept' => 'application/json'));
        $result = $this->object->get_record($request, static::$records['record_1']->get_sbas_id(), "-40");
        $this->assertEquals(400, $result->get_http_code());

        $request = new Request(array(), array(), array(), array(), array(), array('HTTP_Accept' => 'application/json'));
        $result = $this->object->get_record($request, static::$records['record_1']->get_sbas_id(), static::$records['record_1']->get_record_id());
        $this->assertEquals(200, $result->get_http_code());
        $this->assertEquals('application/json', $result->get_content_type());
        $this->assertTrue(is_array(json_decode($result->format(), true)));
    }

    public function testGet_databox_status()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $request = new Request(array(), array(), array(), array(), array(), array('HTTP_Accept' => 'application/json'));
        foreach ($appbox->get_databoxes() as $databox) {
            $result = $this->object->get_databox_status($request, $databox->get_sbas_id());
            $this->assertEquals(200, $result->get_http_code());
            $this->assertEquals('application/json', $result->get_content_type());
            $this->assertTrue(is_array(json_decode($result->format(), true)));
        }
    }

    public function testGet_databox_metadatas()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $request = new Request(array(), array(), array(), array(), array(), array('HTTP_Accept' => 'application/json'));
        foreach ($appbox->get_databoxes() as $databox) {
            $result = $this->object->get_databox_metadatas($request, $databox->get_sbas_id());
            $this->assertEquals(200, $result->get_http_code());
            $this->assertEquals('application/json', $result->get_content_type());
            $this->assertTrue(is_array(json_decode($result->format(), true)));
        }
    }

    public function testGet_databox_terms()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $request = new Request(array(), array(), array(), array(), array(), array('HTTP_Accept' => 'application/json'));
        foreach ($appbox->get_databoxes() as $databox) {
            $result = $this->object->get_databox_terms($request, $databox->get_sbas_id());
            $this->assertEquals(200, $result->get_http_code());
            $this->assertEquals('application/json', $result->get_content_type());
            $this->assertTrue(is_array(json_decode($result->format(), true)));
        }
    }

    public function testSearch_records()
    {
        $request = new Request(array('record_type' => "image"), array(), array(), array(), array(), array('HTTP_Accept' => 'application/json'));
        $result = $this->object->search_records($request);
        $this->assertEquals(200, $result->get_http_code());
        $this->assertEquals('application/json', $result->get_content_type());
        $this->assertTrue(is_array(json_decode($result->format(), true)));
    }

    public function testGet_record_related()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());

        $request = new Request(array(), array(), array(), array(), array(), array('HTTP_Accept' => 'application/json'));
        $result = $this->object->get_record_related($request, static::$records['record_1']->get_sbas_id(), static::$records['record_1']->get_record_id());
        $this->assertEquals(200, $result->get_http_code());
        $this->assertEquals('application/json', $result->get_content_type());
        $this->assertTrue(is_array(json_decode($result->format(), true)));
    }

    public function testGet_record_metadatas()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());

        $request = new Request(array(), array(), array(), array(), array(), array('HTTP_Accept' => 'application/json'));
        $result = $this->object->get_record_metadatas($request, static::$records['record_1']->get_sbas_id(), static::$records['record_1']->get_record_id());
        $this->assertEquals(200, $result->get_http_code());
        $this->assertEquals('application/json', $result->get_content_type());
        $this->assertTrue(is_array(json_decode($result->format(), true)));
    }

    public function testGet_record_status()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());

        $request = new Request();
        $request = new Request(array(), array(), array(), array(), array(), array('HTTP_Accept' => 'application/json'));
        $result = $this->object->get_record_status($request, static::$records['record_1']->get_sbas_id(), static::$records['record_1']->get_record_id());
        $this->assertEquals(200, $result->get_http_code());
        $this->assertEquals('application/json', $result->get_content_type());
        $this->assertTrue(is_array(json_decode($result->format(), true)));
    }

    public function testGet_record_embed()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());

        $request = new Request(array(), array(), array(), array(), array(), array('HTTP_Accept' => 'application/json'));
        $result = $this->object->get_record_embed($request, static::$records['record_1']->get_sbas_id(), static::$records['record_1']->get_record_id());
        $this->assertEquals(200, $result->get_http_code());
        $this->assertEquals('application/json', $result->get_content_type());
        $this->assertTrue(is_array(json_decode($result->format(), true)));
    }

    public function testSet_record_metadatas()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $databox = static::$records['record_1']->get_databox();
        $request = new Request(array("salut" => "salut c'est la fete"), array(), array(), array(), array(), array('HTTP_Accept' => 'application/json'));
        $result = $this->object->set_record_metadatas($request, static::$records['record_1']->get_sbas_id(), static::$records['record_1']->get_record_id());
        $this->assertEquals(400, $result->get_http_code());

        $request = new Request(array("metadatas" => "salut c'est la fete"), array(), array(), array(), array(), array('HTTP_Accept' => 'application/json'));
        $this->object->set_record_metadatas($request, static::$records['record_1']->get_sbas_id(), static::$records['record_1']->get_record_id());
        $this->assertEquals(400, $result->get_http_code());

        if (sizeof(static::$records['record_1']->get_caption()->get_fields()) == 0) {
            $caption_field_value = caption_Field_Value::create(databox_field::get_instance($databox, 1), static::$records['record_1'], 'my value');
        }

//valide metas
        $metadatas = array();

        foreach (static::$records['record_1']->get_databox()->get_meta_structure()->get_elements() as $field) {
            try {
                $values = static::$records['record_1']->get_caption()->get_field($field->get_name())->get_values();
                $value = array_pop($values);
                $meta_id = $value->getId();
            } catch (\Exception $e) {
                $meta_id = null;
            }

            $metadatas[] = array(
                'meta_id'        => $meta_id
                , 'meta_struct_id' => $field->get_id()
                , 'value'          => 'poOM POOM TCHOK ' . $field->get_id()
            );
        }

        $request = new Request(array("metadatas" => $metadatas), array(), array(), array(), array(), array('HTTP_Accept' => 'application/json'));

        $result = $this->object->set_record_metadatas($request, static::$records['record_1']->get_sbas_id(), static::$records['record_1']->get_record_id());

        $response = json_decode($result->format(), true);

        $this->assertEquals($response['meta']['http_code'], 200);

        $this->checkResponseField($result, "record_metadatas", 'array');
    }

    public function testSet_record_status()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $stub = $this->getMock("API_V1_adapter", array("list_record_status"), array(false, &$appbox, bootstrap::getCore()));
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $databox = static::$records['record_1']->get_databox();

        $statusbit = null;
        foreach ($databox->get_statusbits() as $key => $value) {
            $statusbit = $key;
            break;
        }

        if(null === $statusbit) {
            $this->markTestSkipped('No status bit defined in databox');
        }

        $request = new Request(array("salut" => "salut c'est la fete"), array(), array(), array(), array(), array('HTTP_Accept' => 'application/json'));
        $result = $stub->set_record_status($request, static::$records['record_1']->get_sbas_id(), static::$records['record_1']->get_record_id());
        $this->assertEquals(400, $result->get_http_code());

        $request = new Request(array("status" => "salut c'est la fete"), array(), array(), array(), array(), array('HTTP_Accept' => 'application/json'));
        $stub->set_record_status($request, static::$records['record_1']->get_sbas_id(), static::$records['record_1']->get_record_id());
        $this->assertEquals(400, $result->get_http_code());

        $status = array($statusbit => '1');

        $request = new Request(array("status" => $status), array(), array(), array(), array(), array('HTTP_Accept' => 'application/json'));
        //check method use record->get_caption
        $stub->expects($this->once())
            ->method("list_record_status")
            ->will($this->returnValue(new stdClass()));
        //check for metadas fiels in response
        $result = $stub->set_record_status($request, static::$records['record_1']->get_sbas_id(), static::$records['record_1']->get_record_id());
        $this->checkResponseField($result, "status", 'array');
        }

    public function testSet_record_collection()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $stub = $this->getMock("API_V1_adapter", array("list_record"), array(false, &$appbox, bootstrap::getCore()));
        $databox = static::$records['record_1']->get_databox();

        $request = new Request(array("salut" => "salut c'est la fete"), array(), array(), array(), array(), array('HTTP_Accept' => 'application/json'));
        $result = $stub->set_record_collection($request, static::$records['record_1']->get_sbas_id(), static::$records['record_1']->get_record_id());
        $this->assertEquals(400, $result->get_http_code());

        foreach ($appbox->get_databoxes() as $databox) {
            $collections = $databox->get_collections();
            break;
        }

        $collection = array_shift($collections);

        $request = new Request(array("base_id" => $collection->get_base_id()), array(), array(), array(), array(), array('HTTP_Accept' => 'application/json'));
        //check method use record->get_caption
        $stub->expects($this->once())
            ->method("list_record")
            ->will($this->returnValue(new stdClass()));
        //check for metadas fiels in response
        $result = $stub->set_record_collection($request, static::$records['record_1']->get_sbas_id(), static::$records['record_1']->get_record_id());
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
        $request = new Request(array(), array(), array(), array(), array(), array('HTTP_Accept' => 'application/json'));
        $result = $this->object->search_baskets($request);
        $this->assertEquals(200, $result->get_http_code());
        $this->assertEquals('application/json', $result->get_content_type());
        $this->assertTrue(is_array(json_decode($result->format(), true)));
    }

    public function testCreate_basket()
    {
        $request = new Request(array(), array(), array('name' => 'BIG BASKET'), array(), array(), array('HTTP_Accept' => 'application/json'));
        $result = $this->object->create_basket($request);
        $this->assertEquals(200, $result->get_http_code());
        $this->assertEquals('application/json', $result->get_content_type());
        $this->assertTrue(is_array(json_decode($result->format(), true)));

        $n = 0;
        $response = json_decode($result->format(), true);
        $this->assertArrayHasKey('basket', $response['response']);
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $session = $appbox->get_session();
        $usr_id = $session->get_usr_id();

        $em = self::$core->getEntityManager();
        $repo = $em->getRepository('\Entities\Basket');

        /* @var $repo \Repositories\BasketRepository */
        $basket = $repo->findUserBasket($response['response']['basket']['basket_id'], self::$core->getAuthenticatedUser(), true);

        $this->assertTrue($basket instanceof \Entities\Basket);
        $em->remove($basket);
        $em->flush();
    }

    public function testDelete_basket()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $usr_id = $appbox->get_session()->get_usr_id();
        $user = User_Adapter::getInstance($usr_id, $appbox);

        $em = self::$core->getEntityManager();

        $Basket = new Entities\Basket();
        $Basket->setName('Delete test');
        $Basket->setOwner($user);

        $em->persist($Basket);
        $em->flush();

        $ssel_id = $Basket->getId();

        $request = new Request(array(), array(), array(), array(), array(), array('HTTP_Accept' => 'application/json'));
        $result = $this->object->delete_basket($request, $ssel_id);
        $this->assertEquals(200, $result->get_http_code());
        $this->assertEquals('application/json', $result->get_content_type());
        $this->assertTrue(is_array(json_decode($result->format(), true)));

        $repo = $em->getRepository('\Entities\Basket');

        try {
            $repo->findUserBasket($ssel_id, $user, true);
            $this->fail('An exception should have been raised');
        } catch (Exception_NotFound $e) {

        }
    }

    public function testGet_basket()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $usr_id = $appbox->get_session()->get_usr_id();

        $basket = $this->insertOneBasket();

        $request = new Request(array(), array(), array(), array(), array(), array('HTTP_Accept' => 'application/json'));
        $result = $this->object->get_basket($request, $basket->getId());
        $this->assertEquals(200, $result->get_http_code());
        $this->assertEquals('application/json', $result->get_content_type());
        $this->assertTrue(is_array(json_decode($result->format(), true)));
    }

    public function testSet_basket_title()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $usr_id = $appbox->get_session()->get_usr_id();

        $basket = $this->insertOneBasket();

        $request = new Request(array(), array(), array('name' => 'PROUTO'), array(), array(), array('HTTP_Accept' => 'application/json'));
        $result = $this->object->set_basket_title($request, $basket->getId());
        $this->assertEquals(200, $result->get_http_code());
        $this->assertEquals('application/json', $result->get_content_type());
        $this->assertTrue(is_array(json_decode($result->format(), true)));

        $repository = self::$core->getEntityManager()->getRepository('\Entities\Basket');

        $ret_bask = $repository->find($basket->getId());

        $this->assertEquals('PROUTO', $ret_bask->getName());
    }

    public function testSet_basket_description()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $usr_id = $appbox->get_session()->get_usr_id();

        $basket = $this->insertOneBasket();

        $request = new Request(array(), array(), array('description' => 'une belle description'), array(), array(), array('HTTP_Accept' => 'application/json'));
        $result = $this->object->set_basket_description($request, $basket->getId());
        $this->assertEquals(200, $result->get_http_code());
        $this->assertEquals('application/json', $result->get_content_type());
        $this->assertTrue(is_array(json_decode($result->format(), true)));

        $repository = self::$core->getEntityManager()->getRepository('\Entities\Basket');

        $ret_bask = $repository->find($basket->getId());

        $this->assertEquals('une belle description', $ret_bask->getDescription());
    }

    public function testSearch_publications()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $request = new Request(array(), array(), array(), array(), array(), array('HTTP_Accept' => 'application/json'));
        $feed = Feed_Adapter::create($appbox, self::$user, "hello", "salut");
        $result = $this->object->search_publications($request, self::$user);
        $this->checkResponseField($result, "feeds", 'array');
        $feed->delete();
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
        $appbox = appbox::get_instance(\bootstrap::getCore());

        $date = new DateTime();
        $request = new Request(array(), array(), array(), array(), array(), array('HTTP_Accept'    => 'application/json'));
        $feed = Feed_Adapter::create($appbox, self::$user, "hello", "salut");
        $feed_publisher = Feed_Publisher_Adapter::getPublisher($appbox, $feed, self::$user);
        $feed_entry = Feed_Entry_Adapter::create($appbox, $feed, $feed_publisher, "coucou", "hello", "me", "my@email.com");
        $feed_entry_item = Feed_Entry_Item::create($appbox, $feed_entry, static::$records['record_1']);
        $coll = Feed_Collection::load_all($appbox, self::$user);
        foreach ($coll->get_feeds() as $feed) {
            $result = $this->object->get_publication($request, $feed->get_id(), self::$user);
            $this->checkResponseField($result, "feed", 'array');
            $this->checkResponseField($result, "entries", 'array');
            $this->checkResponseField($result, "offset_start", 'integer');
            $this->checkResponseField($result, "per_page", 'integer');
        }
        $feed->delete();
    }

    public function testSearch_users()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testGet_user_acces()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testAdd_user()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    protected function checkResponseField(API_V1_result $result, $field, $type)
    {
        $response = json_decode($result->format(), true);
        $this->assertArrayHasKey($field, $response['response']);
        $this->assertInternalType($type, $response['response'][$field]);
    }
}

