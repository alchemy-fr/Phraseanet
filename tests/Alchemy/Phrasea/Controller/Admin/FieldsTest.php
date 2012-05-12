<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class ControllerFieldsTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

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
    public function testCheckMulti()
    {
        $appbox = \appbox::get_instance(\bootstrap::getCore());
        $databox = array_shift($appbox->get_databoxes());

        $field = \databox_field::create($databox, "test" . time());
        $source = $field->get_source();

        $this->client->request("GET", "/fields/checkmulti/", array(
            'souce' => $source, 'multi' => 'false'));

        $response = $this->client->getResponse();
        $this->assertEquals("application/json", $response->headers->get("content-type"));
        $datas = json_decode($response->getContent());
        $this->assertTrue(is_object($datas));
        $this->assertTrue( ! ! $datas->result);
        $this->assertEquals($field->is_multi(),  ! ! $datas->is_multi);
        $field->delete();
    }

    public function testCheckReadOnly()
    {
        $appbox = \appbox::get_instance(\bootstrap::getCore());
        $databox = array_shift($appbox->get_databoxes());

        $field = \databox_field::create($databox, "test" . time());
        $source = $field->get_source();

        $this->client->request("GET", "/fields/checkreadonly/", array(
            'souce'    => $source, 'readonly' => 'false'));

        $response = $this->client->getResponse();
        $this->assertEquals("application/json", $response->headers->get("content-type"));
        $datas = json_decode($response->getContent());
        $this->assertTrue(is_object($datas));
        $this->assertTrue( ! ! $datas->result);
        $this->assertEquals($field->is_readonly(),  ! ! $datas->is_readonly);

        $field->delete();
    }
}
