<?php

use Alchemy\Phrasea\Core\Configuration;

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class ControllerFieldsTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    /**
     * Default route test
     */
    public function testCheckMulti()
    {
        $appbox = self::$application['phraseanet.appbox'];
        $databox = array_shift($appbox->get_databoxes());

        $tag = new PHPExiftool\Driver\Tag\IPTC\ObjectName();

        $field = \databox_field::create(self::$application, $databox, "test" . time(), false);
        $field->set_tag($tag)->save();

        $this->client->request("GET", "/admin/fields/checkmulti/", array(
            'source' => $tag->getTagname(), 'multi'  => 'false'));

        $response = $this->client->getResponse();
        $this->assertEquals("application/json", $response->headers->get("content-type"));
        $datas = json_decode($response->getContent());
        $this->assertTrue(is_object($datas));
        $this->assertTrue($datas->result);
        $this->assertEquals($field->is_multi(), $datas->is_multi);
        $field->delete();
    }

    public function testCheckReadOnly()
    {
        $appbox = self::$application['phraseanet.appbox'];
        $databox = array_shift($appbox->get_databoxes());

        $tag = new PHPExiftool\Driver\Tag\IPTC\ObjectName();

        $field = \databox_field::create(self::$application, $databox, "test" . time(), false);
        $field->set_tag($tag)->save();

        $this->client->request("GET", "/admin/fields/checkreadonly/", array(
            'source'   => $tag->getTagname(), 'readonly' => 'false'));

        $response = $this->client->getResponse();
        $this->assertEquals("application/json", $response->headers->get("content-type"));
        $datas = json_decode($response->getContent());
        $this->assertTrue(is_object($datas));
        $this->assertTrue($datas->result);
        $this->assertEquals($field->is_readonly(), $datas->is_readonly);

        $field->delete();
    }
}
