<?php

namespace Alchemy\Tests\Phrasea\Controller\Admin;

use PHPExiftool\Driver\Tag\IPTC\ObjectName;

/**
 * @group functional
 * @group legacy
 * @group authenticated
 * @group web
 */
class FieldsTest extends \PhraseanetAuthenticatedWebTestCase
{
    public function testRoot()
    {
        $databoxes = $this->getApplication()->getDataboxes();
        $databox = array_shift($databoxes);

        $response = $this->request("GET", "/admin/fields/" . $databox->get_sbas_id());

        $this->assertTrue($response->isOk());
    }

    public function testLanguage()
    {
        $response = $this->request("GET", "/admin/fields/language.json");

        $this->assertTrue($response->isOk());
        $this->assertEquals("application/json", $response->headers->get("content-type"));
    }

    public function testGetTag()
    {
        $tag = new ObjectName();

        $response = $this->request("GET", "/admin/fields/tags/".$tag->getTagname());

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
        $response = $this->request("GET", "/admin/fields/dc-fields");

        $this->assertEquals("application/json", $response->headers->get("content-type"));

        $data = json_decode($response->getContent(), true);
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
        $response = $this->request("GET", "/admin/fields/vocabularies");

        $this->assertEquals("application/json", $response->headers->get("content-type"));

        $data = json_decode($response->getContent(), true);

        $this->assertInternalType('array', $data);

        foreach ($data as $vocabulary) {
            $this->assertArrayHasKey('type', $vocabulary);
            $this->assertArrayHasKey('name', $vocabulary);
        }
    }

    public function testGetVocabulary()
    {
        $response = $this->request("GET", "/admin/fields/vocabularies/user");

        $this->assertEquals("application/json", $response->headers->get("content-type"));

        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('type', $data);
        $this->assertEquals('User', $data['type']);
        $this->assertArrayHasKey('name', $data);
    }

    public function testSearchTag()
    {
        $response = $this->request("GET", "/admin/fields/tags/search?term=xmp-exif");

        $this->assertEquals("application/json", $response->headers->get("content-type"));

        $data = json_decode($response->getContent(), true);

        $this->assertGreaterThan(90, count($data));

        foreach ($data as $tag) {
            $this->assertArrayHasKey('id', $tag);
            $this->assertArrayHasKey('label', $tag);
            $this->assertArrayHasKey('value', $tag);
            $this->assertTrue(false !== strpos($tag['id'], 'xmp'));
        }
    }

    public function testUpdateFields()
    {
        $databoxes = $this->getApplication()->getDataboxes();
        $databox = array_shift($databoxes);
        $fieldObjects = [];
        // create two fields
        $fields = [
            [
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
                'vocabulary-type' => null,
                'vocabulary-restricted' => false,
            ], [
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
                'vocabulary-type' => null,
                'vocabulary-restricted' => false,
        ]];

        foreach ($fields as $fieldData) {
            $field = \databox_field::create($this->getApplication(), $databox, $fieldData['name']);
            $field
                ->set_thumbtitle($fieldData['thumbtitle'])
                ->set_tag(\databox_field::loadClassFromTagName($fieldData['tag']))
                ->set_business($fieldData['business'])
                ->set_indexable($fieldData['indexable'])
                ->set_required($fieldData['required'])
                ->set_separator($fieldData['separator'])
                ->set_readonly($fieldData['readonly'])
                ->set_type($fieldData['type'])
                ->set_tbranch($fieldData['tbranch'])
                ->set_report($fieldData['report'])
                ->setVocabularyControl(null)
                ->setVocabularyRestricted(false)
                ->set_multi($fieldData['multi']);
            $field->save();
            $fieldObjects[] = $field;
        }

        // get body
        $body = $databox->get_meta_structure()->toArray();

        // change some body data
        $body[count($body) - 2]['business'] = true;
        $body[count($body) - 2]['indexable'] = false;
        $body[count($body) - 1]['readonly'] = true;
        $body[count($body) - 1]['required'] = false;

        $response = $this->request("PUT", sprintf("/admin/fields/%d/fields", $databox->get_sbas_id()), [], [], json_encode($body));

        $this->assertEquals("application/json", $response->headers->get("content-type"));

        $data = json_decode($response->getContent(), true);

        $this->assertTrue(is_array($data));

        // expect last 2 fields from body equals last 2 fields from response
        $this->assertEquals(array_splice($body, -2), array_splice($data, -2));

        // delete created fields
        foreach ($fieldObjects as $field) {
            $field->delete();
        }
    }

    public function testCreateField()
    {
        $databoxes = $this->getApplication()->getDataboxes();
        /** @var \databox $databox */
        $databox = array_shift($databoxes);

        $body = json_encode([
            'sbas-id' => $databox->get_sbas_id(),
            'name' => 'testfield' . mt_rand(),
            'multi' => true,
            'thumbtitle' => false,
            'tag' => 'XMP:XMP',
            'business' => false,
            'aggregable' => 0,
            'indexable' => true,
            'required' => true,
            'labels' => [
                'en' => 'Label',
                'fr' => 'Libellé',
                'de' => null,
                'nl' => null,
            ],
            'separator' => '=;',
            'readonly' => false,
            'type' => 'string',
            'tbranch' => '',
            'report' => true,
            'dces-element' => null,
            'vocabulary-type' => 'User',
            'vocabulary-restricted' => true,
            'gui_editable' => true,
            'gui_visible' => true,
            'generate_cterms' => true,
        ]);

        $response = $this->request("POST", sprintf("/admin/fields/%d/fields", $databox->get_sbas_id()), [], [], $body);

        $this->assertEquals("application/json", $response->headers->get("content-type"));

        $data = json_decode($response->getContent(), true);

        $this->assertTrue(is_array($data));

        $dataWithoutIds = $data;
        unset($dataWithoutIds['id']);
        unset($dataWithoutIds['sorter']);

        $this->assertEquals(json_decode($body, true), $dataWithoutIds);

        $field = $databox->get_meta_structure()->get_element($data['id']);
        $field->delete();
    }

    public function testListField()
    {
        $databoxes = $this->getApplication()->getDataboxes();
        $databox = array_shift($databoxes);

        $response = $this->request("GET", sprintf("/admin/fields/%d/fields", $databox->get_sbas_id()));

        $this->assertEquals("application/json", $response->headers->get("content-type"));

        $data = json_decode($response->getContent(), true);

        $this->assertInternalType('array', $data);

        foreach ($data as $field) {
            $this->assertField($field);
        }
    }

    public function testGetField()
    {
        $app = $this->getApplication();
        $databox = $this->getFirstDatabox($app);

        $field = \databox_field::create($app, $databox, 'testfield' . mt_rand());

        $data = $field->toArray();

        $response = $this->request("GET", sprintf("/admin/fields/%d/fields/%d", $databox->get_sbas_id(), $field->get_id()));

        $this->assertEquals("application/json", $response->headers->get("content-type"));

        $this->assertEquals($data, json_decode($response->getContent(), true));

        $field->delete();
    }

    public function testUpdateField()
    {
        $app = $this->getApplication();
        $databox = $this->getFirstDatabox($app);

        $field = \databox_field::create($app, $databox, 'testfield' . mt_rand());

        $data = $field->toArray();

        $data['business'] = true;
        $data['vocabulary-type'] = 'User';

        $response = $this->request("PUT", sprintf("/admin/fields/%d/fields/%d", $databox->get_sbas_id(), $field->get_id()), [], [], json_encode($data));

        $this->assertEquals($data, json_decode($response->getContent(), true));

        $field->delete();
    }

    public function testDeleteField()
    {
        $app = $this->getApplication();
        $databox = $this->getFirstDatabox($app);

        $field = \databox_field::create($app, $databox, 'testfield' . mt_rand());
        $fieldId = $field->get_id();

        $data = $field->toArray();

        $data['business'] = true;
        $data['vocabulary-type'] = 'User';

        $response = $this->request("DELETE", sprintf("/admin/fields/%d/fields/%d", $databox->get_sbas_id(), $field->get_id()), [], [], json_encode($data));

        $this->assertEquals('', $response->getContent());
        $this->assertEquals(204, $response->getStatusCode());

        try {
            $databox->get_meta_structure()->get_element($fieldId);
            $this->fail('Should have raise an exception');
        } catch (\Exception $e) {

        }
    }

    private function assertField($field)
    {
        $properties = [
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
        ];

        foreach ($properties as $property) {
            $this->assertArrayHasKey($property, $field);
        }
    }
}
