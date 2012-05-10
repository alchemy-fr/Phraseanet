<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class DescriptionTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;
    protected static $need_records = false;

    public function createApplication()
    {
        return require __DIR__ . '/../../../../../lib/Alchemy/Phrasea/Application/Admin.php';
    }

    public function setUp()
    {
        parent::setUp();
        $this->client = $this->createClient();
    }

    /**
     * Default route test
     */
    public function testRouteDescription()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $databox = array_shift($appbox->get_databoxes());
        $name = "testtest" . uniqid();
        $field = \databox_field::create($databox, $name);
        $id = $field->get_id();
        $this->client->request("POST", "/description/" . $databox->get_sbas_id() . "/", array(
            'field_ids' => array($id)
            , 'name_' . $id       => $name
            , 'multi_' . $id      => 1
            , 'indexable_' . $id  => 1
            , 'src_' . $id        => '/rdf:RDF/rdf:Description/IPTC:SupplementalCategories'
            , 'required_' . $id   => 0
            , 'readonly_' . $id   => 0
            , 'type_' . $id       => 'string'
            , 'vocabulary_' . $id => 'User'
        ));

        $this->assertTrue($this->client->getResponse()->isRedirect());
        $field->delete();
    }

    public function testPostDelete()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $databox = array_shift($appbox->get_databoxes());
        $name = "test" . uniqid();
        $field = \databox_field::create($databox, $name);
        $id = $field->get_id();

        $this->client->request("POST", "/description/" . $databox->get_sbas_id() . "/", array(
            'todelete_ids' => array($id)
        ));

        $this->assertTrue($this->client->getResponse()->isRedirect());

        try {
            $field = \databox_field::get_instance($databox, $id);
            $field->delete();
            $this->fail("should raise an exception");
        } catch (\Exception $e) {

        }
    }

    public function testPostCreate()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $databox = array_shift($appbox->get_databoxes());

        $name = 'test' . uniqid();

        $this->client->request("POST", "/description/" . $databox->get_sbas_id() . "/", array(
            'newfield' => $name
        ));

        $this->assertTrue($this->client->getResponse()->isRedirect());

        $fields = $databox->get_meta_structure();
        $find = false;

        foreach ($fields as $field) {
            if ($field->get_name() === databox_field::generateName($name)) {
                $field->delete();
                $find = true;
            }
        }

        if ( ! $find) {
            $this->fail("should have create a new field");
        }
    }

    public function testPostDescriptionException()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $databox = array_shift($appbox->get_databoxes());

        $this->client->request("POST", "/description/" . $databox->get_sbas_id() . "/", array(
            'todelete_ids' => array('unknow_id')
        ));

        $this->assertTrue($this->client->getResponse()->isRedirect());

        $name = "test" . uniqid();
        $field = \databox_field::create($databox, $name);
        $id = $field->get_id();
        $this->client->request("POST", "/description/" . $databox->get_sbas_id() . "/", array(
            'field_ids' => array($id)
            , 'name_' . $id       => $name
            , 'multi_' . $id      => 1
            , 'indexable_' . $id  => 1
            , 'src_' . $id        => '/rdf:RDF/rdf:Description/IPTC:SupplementalCategories'
            , 'required_' . $id   => 0
            , 'readonly_' . $id   => 0
            , 'type_' . $id       => 'string'
            , 'vocabulary_' . $id => 'Unknow_Vocabulary'
        ));

        $this->assertTrue($this->client->getResponse()->isRedirect());
        $field->delete();

        $name = "test" . uniqid();
        $field = \databox_field::create($databox, $name);
        $id = $field->get_id();
        $this->client->request("POST", "/description/" . $databox->get_sbas_id() . "/", array(
            'field_ids' => array($id)
            , 'multi_' . $id      => 1
            , 'indexable_' . $id  => 1
            , 'src_' . $id        => '/rdf:RDF/rdf:Description/IPTC:SupplementalCategories'
            , 'required_' . $id   => 0
            , 'readonly_' . $id   => 0
            , 'type_' . $id       => 'string'
            , 'vocabulary_' . $id => 'Unknow_Vocabulary'
        ));

        $this->assertTrue($this->client->getResponse()->isRedirect());
        $field->delete();

        $name = "test" . uniqid();
        $field = \databox_field::create($databox, $name);
        $field->set_multi(false);
        $field->set_indexable(false);
        $field->set_required(true);
        $field->set_readonly(true);
        $id = $field->get_id();
        $this->client->request("POST", "/description/" . $databox->get_sbas_id() . "/", array(
            'field_ids' => array($id)
            , 'name_' . $id       => $name
            , 'multi_' . $id      => 1
            , 'indexable_' . $id  => 1
            , 'src_' . $id        => 'unknow_Source'
            , 'required_' . $id   => 0
            , 'readonly_' . $id   => 0
            , 'type_' . $id       => 'string'
            , 'vocabulary_' . $id => 'Unknow_Vocabulary'
        ));

        $this->assertTrue($this->client->getResponse()->isRedirect());
        $this->assertTrue($field->is_readonly());
        $this->assertTrue($field->is_required());
        $this->assertFalse($field->is_multi());
        $this->assertFalse($field->is_indexable());
        $field->delete();


        $name = "test" . uniqid();
        $field = \databox_field::create($databox, $name);
        $id = $field->get_id();
        $this->client->request("POST", "/description/" . $databox->get_sbas_id() . "/", array(
            'field_ids' => array('unknow_id')
            , 'name_' . $id       => $name
            , 'multi_' . $id      => 1
            , 'indexable_' . $id  => 1
            , 'src_' . $id        => '/rdf:RDF/rdf:Description/IPTC:SupplementalCategories'
            , 'required_' . $id   => 0
            , 'readonly_' . $id   => 0
            , 'type_' . $id       => 'string'
            , 'vocabulary_' . $id => 'Unknow_Vocabulary'
        ));

        $this->assertTrue($this->client->getResponse()->isRedirect());
        $field->delete();
    }

    public function testPostDescriptionRights()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());

        $session = $appbox->get_session();
        $auth = new Session_Authentication_None(self::$user_alt1);
        $session->authenticate($auth);

        $databox = array_shift($appbox->get_databoxes());
        $name = "test" . uniqid();
        $field = \databox_field::create($databox, $name);
        $id = $field->get_id();
        $this->client->request("POST", "/description/" . $databox->get_sbas_id() . "/", array(
            'field_ids' => array($id)
            , 'name_' . $id       => $name
            , 'multi_' . $id      => 1
            , 'indexable_' . $id  => 1
            , 'src_' . $id        => '/rdf:RDF/rdf:Description/IPTC:SupplementalCategories'
            , 'required_' . $id   => 0
            , 'readonly_' . $id   => 0
            , 'type_' . $id       => 'string'
            , 'vocabulary_' . $id => 'User'
        ));
        $this->assertFalse($this->client->getResponse()->isOk());
        $this->assertEquals("You are not allowed to access this zone", $this->client->getResponse()->getContent());
        $field->delete();
    }

    public function testGetDescriptionException()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());

        $session = $appbox->get_session();
        $auth = new Session_Authentication_None(self::$user_alt1);
        $session->authenticate($auth);

        $databox = array_shift($appbox->get_databoxes());

        $this->client->request("GET", "/description/" . $databox->get_sbas_id() . "/");
        $this->assertFalse($this->client->getResponse()->isOk());
        $this->assertEquals("You are not allowed to access this zone", $this->client->getResponse()->getContent());
    }

    public function testGetDescription()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $databox = array_shift($appbox->get_databoxes());

        $this->client->request("GET", "/description/" . $databox->get_sbas_id() . "/");
        $this->assertTrue($this->client->getResponse()->isOk());
    }
}
