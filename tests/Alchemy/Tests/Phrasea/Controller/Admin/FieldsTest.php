<?php

namespace Alchemy\Tests\Phrasea\Controller\Admin;

use PHPExiftool\Driver\Tag\IPTC\ObjectName;

class ControllerFieldsTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    /**
     * Default route test
     */
    public function testCheckMulti()
    {
        $databoxes = self::$DI['app']['phraseanet.appbox']->get_databoxes();
        $databox = array_shift($databoxes);

        $tag = new ObjectName();

        $field = \databox_field::create(self::$DI['app'], $databox, "test" . time(), false);
        $field->set_tag($tag)->save();

        self::$DI['client']->request("GET", "/admin/fields/checkmulti/", array(
            'source' => $tag->getTagname(), 'multi'  => 'false'));

        $response = self::$DI['client']->getResponse();
        $this->assertEquals("application/json", $response->headers->get("content-type"));
        $datas = json_decode($response->getContent());
        $this->assertTrue(is_object($datas));
        $this->assertTrue($datas->result);
        $this->assertEquals($field->is_multi(), $datas->is_multi);
        $field->delete();
    }

    public function testCheckReadOnly()
    {
        $databoxes = self::$DI['app']['phraseanet.appbox']->get_databoxes();
        $databox = array_shift($databoxes);

        $tag = new ObjectName();

        $field = \databox_field::create(self::$DI['app'], $databox, "test" . time(), false);
        $field->set_tag($tag)->save();

        self::$DI['client']->request("GET", "/admin/fields/checkreadonly/", array(
            'source'   => $tag->getTagname(), 'readonly' => 'false'));

        $response = self::$DI['client']->getResponse();
        $this->assertEquals("application/json", $response->headers->get("content-type"));
        $datas = json_decode($response->getContent());
        $this->assertTrue(is_object($datas));
        $this->assertTrue($datas->result);
        $this->assertEquals($field->is_readonly(), $datas->is_readonly);

        $field->delete();
    }
}
