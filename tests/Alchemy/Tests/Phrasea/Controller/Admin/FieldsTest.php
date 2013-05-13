<?php

namespace Alchemy\Tests\Phrasea\Controller\Admin;

use PHPExiftool\Driver\Tag\IPTC\ObjectName;
use Alchemy\Phrasea\Vocabulary\Controller as VocabularyController;

class ControllerFieldsTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    public function testGetTag()
    {
        $tag = new ObjectName();

        self::$DI['client']->request("GET", "/admin/fields/tags/".$tag->getTagname());

        $response = self::$DI['client']->getResponse();

        $this->assertEquals("application/json", $response->headers->get("content-type"));
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('description', $data);
        $this->assertArrayHasKey('tagname', $data);
        $this->assertEquals($tag->getTagname(), $data['tagname']);
    }

    public function testListDcFields()
    {
        self::$DI['client']->request("GET", "/admin/fields/dc-fields");

        $response = self::$DI['client']->getResponse()->getContent();

        $this->assertEquals("application/json", self::$DI['client']->getResponse()->headers->get("content-type"));

        $data = json_decode($response, true);
        $this->assertInternalType('array', $data);

        foreach ($data as $dc) {
            $this->assertArrayHasKey('label', $dc);
            $this->assertArrayHasKey('definition', $dc);
            $this->assertArrayHasKey('URI', $dc);
        }

        $this->assertCount(15, $data);
    }

    public function testListVocabularies()
    {
        self::$DI['client']->request("GET", "/admin/fields/vocabularies");

        $response = self::$DI['client']->getResponse()->getContent();
        $this->assertEquals("application/json", self::$DI['client']->getResponse()->headers->get("content-type"));

        $data = json_decode($response, true);

        $this->assertInternalType('array', $data);

        foreach ($data as $vocabulary) {
            $this->assertArrayHasKey('type', $vocabulary);
            $this->assertArrayHasKey('name', $vocabulary);

            $voc = VocabularyController::get(self::$DI['app'], $vocabulary['type']);
            $this->assertInstanceOf('Alchemy\Phrasea\Vocabulary\ControlProvider\ControlProviderInterface', $voc);
        }
    }

    public function testGetVocabulary()
    {
        self::$DI['client']->request("GET", "/admin/fields/vocabularies/user");

        $response = self::$DI['client']->getResponse()->getContent();
        $this->assertEquals("application/json", self::$DI['client']->getResponse()->headers->get("content-type"));

        $data = json_decode($response, true);

        $this->assertArrayHasKey('type', $data);
        $this->assertEquals('User', $data['type']);
        $this->assertArrayHasKey('name', $data);

        $voc = VocabularyController::get(self::$DI['app'], $data['type']);
        $this->assertInstanceOf('Alchemy\Phrasea\Vocabulary\ControlProvider\UserProvider', $voc);
    }

    public function testSearchTag()
    {
        self::$DI['client']->request("GET", "/admin/fields/tags/search?term=xmp-exif");

        $response = self::$DI['client']->getResponse()->getContent();
        $this->assertEquals("application/json", self::$DI['client']->getResponse()->headers->get("content-type"));

        $data = json_decode($response, true);

        $this->assertGreaterThan(90, count($data));

        foreach($data as $tag) {
            $this->assertArrayHasKey('id', $tag);
            $this->assertArrayHasKey('label', $tag);
            $this->assertArrayHasKey('value', $tag);
            $this->assertTrue(false !== strpos($tag['id'], 'xmp'));
        }
    }

    public function testCreateField()
    {
        $databoxes = self::$DI['app']['phraseanet.appbox']->get_databoxes();
        $databox = array_shift($databoxes);

        $body = json_encode(array(
            'sbas-id' => $databox->get_sbas_id(),
            'name' => 'testfield' . mt_rand(),
            'multi' => true,
            'thumbtitle' => false,
            'tag' => 'XMP:XMP',
            'business' => false,
            'indexable' => true,
            'required' => true,
            'separator' => '=;',
            'readonly' => false,
            'type' => 'string',
            'tbranch' => '',
            'report' => true,
            'dces-element' => null,
            'vocabulary-type' => 'User',
            'vocabulary-restricted' => true,
        ));

        self::$DI['client']->request("POST", sprintf("/admin/fields/%d/fields", $databox->get_sbas_id()), array(), array(), array(), $body);

        $response = self::$DI['client']->getResponse()->getContent();
        $this->assertEquals("application/json", self::$DI['client']->getResponse()->headers->get("content-type"));

        $data = json_decode($response, true);

        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('field', $data);

        $dataWithoutIds = $data['field'];
        unset($dataWithoutIds['id']);
        unset($dataWithoutIds['sorter']);

        $this->assertEquals(json_decode($body, true), $dataWithoutIds);

        $field = \databox_field::get_instance(self::$DI['app'], $databox, $data['field']['id']);
        $field->delete();
    }

    public function testListField()
    {
        $databoxes = self::$DI['app']['phraseanet.appbox']->get_databoxes();
        $databox = array_shift($databoxes);

        self::$DI['client']->request("GET", sprintf("/admin/fields/%d/fields", $databox->get_sbas_id()));

        $response = self::$DI['client']->getResponse()->getContent();
        $this->assertEquals("application/json", self::$DI['client']->getResponse()->headers->get("content-type"));

        $data = json_decode($response, true);

        $this->assertInternalType('array', $data);

        foreach ($data as $field) {
            $this->assertField($field);
        }
    }

    public function testGetField()
    {
        $databoxes = self::$DI['app']['phraseanet.appbox']->get_databoxes();
        $databox = array_shift($databoxes);

        $field = \databox_field::create(self::$DI['app'], $databox, 'testfield' . mt_rand(), false);

        $data = $field->toArray();

        self::$DI['client']->request("GET", sprintf("/admin/fields/%d/fields/%d", $databox->get_sbas_id(), $field->get_id()));

        $response = self::$DI['client']->getResponse()->getContent();
        $this->assertEquals("application/json", self::$DI['client']->getResponse()->headers->get("content-type"));

        $this->assertEquals($data, json_decode($response, true));

        $field->delete();
    }

    public function testUpdateField()
    {
        $databoxes = self::$DI['app']['phraseanet.appbox']->get_databoxes();
        $databox = array_shift($databoxes);

        $field = \databox_field::create(self::$DI['app'], $databox, 'testfield' . mt_rand(), false);

        $data = $field->toArray();

        $data['business'] = true;
        $data['vocabulary-type'] = 'User';

        self::$DI['client']->request("PUT", sprintf("/admin/fields/%d/fields/%d", $databox->get_sbas_id(), $field->get_id()), array(), array(), array(), json_encode($data));

        $response = self::$DI['client']->getResponse()->getContent();
        $this->assertEquals($data, json_decode($response, true));

        $field->delete();
    }

    public function testDeleteField()
    {
        $databoxes = self::$DI['app']['phraseanet.appbox']->get_databoxes();
        $databox = array_shift($databoxes);

        $field = \databox_field::create(self::$DI['app'], $databox, 'testfield' . mt_rand(), false);
        $fieldId = $field->get_id();

        $data = $field->toArray();

        $data['business'] = true;
        $data['vocabulary-type'] = 'User';

        self::$DI['client']->request("DELETE", sprintf("/admin/fields/%d/fields/%d", $databox->get_sbas_id(), $field->get_id()), array(), array(), array(), json_encode($data));

        $response = self::$DI['client']->getResponse()->getContent();
        $this->assertEquals('', $response);
        $this->assertEquals(204, self::$DI['client']->getResponse()->getStatusCode());

        try {
            \databox_field::get_instance(self::$DI['app'], $databox, $fieldId);
            $this->fail('Should have raise an exception');
        } catch (\Exception $e) {

        }
    }

    private function assertField($field)
    {
        $properties = array(
            'name',
            'multi',
            'thumbtitle',
            'tag',
            'business',
            'indexable',
            'required',
            'separator',
            'readonly',
            'type',
            'tbranch',
            'report',
            'dces-element',
            'vocabulary-type',
            'vocabulary-restricted'
        );

        foreach ($properties as $property) {
            $this->assertArrayHasKey($property, $field);
        }
    }
}
