<?php

namespace Alchemy\Phrasea\SearchEngine;

use Symfony\Component\Process\Process;

require_once __DIR__ . '/../../../PhraseanetPHPUnitAuthenticatedAbstract.class.inc';

abstract class SearchEngineAbstractTest extends \PhraseanetPHPUnitAbstract
{
    protected static $searchEngine;

    public function setUp()
    {
        parent::setUp();
        $appbox = \appbox::get_instance(\bootstrap::getCore());
        foreach ($appbox->get_databoxes() as $databox) {
            break;
        }

        if ( ! self::$searchEngine instanceof SearchEngineInterface) {
            $this->markTestSkipped('Unable to initialize search Engine');
        }

        $options = new SearchEngineOptions();
        $options->onCollections($databox->get_collections());

        self::$searchEngine->setOptions($options);
    }

    protected function updateIndex()
    {
        return $this;
    }

    public function testAddRecord()
    {
        $record = self::$records['record_24'];

        self::$searchEngine->removeRecord($record);
        $this->updateIndex();

        $results = self::$searchEngine->query('recordid=' . $record->get_record_id(), 0, 1);

        $this->assertEquals(0, $results->total());

        self::$searchEngine->addRecord($record);
        $this->updateIndex();

        $results = self::$searchEngine->query('recordid=' . $record->get_record_id(), 0, 1);

        $this->assertEquals(1, $results->total());

        self::$searchEngine->removeRecord($record);
        $this->updateIndex();
    }

    public function testUpdateRecord()
    {
        $record = self::$records['record_24'];

        self::$searchEngine->addRecord($record);
        $this->updateIndex();

        $query_string = 'boomboklot' . $record->get_record_id();

        $results = self::$searchEngine->query($query_string, 0, 1);
        $this->assertEquals(0, $results->total());

        $toupdate = array();

        foreach ($record->get_databox()->get_meta_structure()->get_elements() as $field) {
            try {
                $values = $record->get_caption()->get_field($field->get_name())->get_values();
                $value = array_pop($values);
                $meta_id = $value->getId();
            } catch (\Exception $e) {
                $meta_id = null;
            }

            $toupdate[$field->get_id()] = array(
                'meta_id'        => $meta_id
                , 'meta_struct_id' => $field->get_id()
                , 'value'          => $query_string
            );
            break;
        }

        $record->set_metadatas($toupdate);

        self::$searchEngine->updateRecord($record);
        $this->updateIndex();

        $results = self::$searchEngine->query($query_string, 0, 1);

        $this->assertEquals(1, $results->total());

        self::$searchEngine->removeRecord($record);
        $this->updateIndex();
    }

    public function testUpdateRecordFR()
    {
        $appbox = \appbox::get_instance(\bootstrap::getCore());
        foreach ($appbox->get_databoxes() as $databox) {
            break;
        }
        $options = new SearchEngineOptions();
        $options->onCollections($databox->get_collections());
        $options->useStemming(true);
        $options->setLocale('fr');

        self::$searchEngine->setOptions($options);

        $record = self::$records['record_24'];

        self::$searchEngine->addRecord($record);
        $this->updateIndex();

        $query_string = 'boomboklot' . $record->get_record_id() . 'fr';

        $results = self::$searchEngine->query($query_string, 0, 1);
        $this->assertEquals(0, $results->total());

        $toupdate = array();

        foreach ($record->get_databox()->get_meta_structure()->get_elements() as $field) {
            try {
                $values = $record->get_caption()->get_field($field->get_name())->get_values();
                $value = array_pop($values);
                $meta_id = $value->getId();
            } catch (\Exception $e) {
                $meta_id = null;
            }

            $toupdate[$field->get_id()] = array(
                'meta_id'        => $meta_id
                , 'meta_struct_id' => $field->get_id()
                , 'value'          => $query_string
            );
            break;
        }

        $record->set_metadatas($toupdate);

        self::$searchEngine->updateRecord($record);
        $this->updateIndex();

        $results = self::$searchEngine->query($query_string, 0, 1);

        $this->assertEquals(1, $results->total());

        self::$searchEngine->removeRecord($record);
        $this->updateIndex();
    }

    public function testUpdateQueryOnField()
    {
        $appbox = \appbox::get_instance(\bootstrap::getCore());
        foreach ($appbox->get_databoxes() as $databox) {
            break;
        }
        $options = new SearchEngineOptions();
        $options->onCollections($databox->get_collections());

        $record = self::$records['record_24'];

        self::$searchEngine->addRecord($record);
        $this->updateIndex();

        $query_string = 'boomboklot' . $record->get_record_id() . 'onfield';

        $results = self::$searchEngine->query($query_string, 0, 1);
        $this->assertEquals(0, $results->total());

        $toupdate = array();

        foreach ($record->get_databox()->get_meta_structure()->get_elements() as $field) {
            try {
                $values = $record->get_caption()->get_field($field->get_name())->get_values();
                $value = array_pop($values);
                $meta_id = $value->getId();
            } catch (\Exception $e) {
                $meta_id = null;
            }

            $options->setFields(array($field));

            $toupdate[$field->get_id()] = array(
                'meta_id'        => $meta_id
                , 'meta_struct_id' => $field->get_id()
                , 'value'          => $query_string
            );
            break;
        }

        self::$searchEngine->setOptions($options);

        $record->set_metadatas($toupdate);

        self::$searchEngine->updateRecord($record);
        $this->updateIndex();

        $results = self::$searchEngine->query($query_string, 0, 1);

        $this->assertEquals(1, $results->total());

        self::$searchEngine->removeRecord($record);
        $this->updateIndex();
    }

    public function testBusinessFieldAvailable()
    {
        $appbox = \appbox::get_instance(\bootstrap::getCore());
        foreach ($appbox->get_databoxes() as $databox) {
            break;
        }
        $options = new SearchEngineOptions();
        $options->onCollections($databox->get_collections());

        $record = self::$records['record_24'];

        self::$searchEngine->addRecord($record);
        $this->updateIndex();

        $query_string = 'boomboklot' . $record->get_record_id() . 'businessAvailable';

        $results = self::$searchEngine->query($query_string, 0, 1);
        $this->assertEquals(0, $results->total());

        $toupdate = array();

        foreach ($record->get_databox()->get_meta_structure()->get_elements() as $field) {

            if ( ! $field->isBusiness()) {
                continue;
            }

            try {
                $values = $record->get_caption()->get_field($field->get_name())->get_values();
                $value = array_pop($values);
                $meta_id = $value->getId();
            } catch (\Exception $e) {
                $meta_id = null;
            }

            $options->allowBusinessFieldsOn(array($record->get_collection()));

            $toupdate[$field->get_id()] = array(
                'meta_id'        => $meta_id
                , 'meta_struct_id' => $field->get_id()
                , 'value'          => $query_string
            );
            break;
        }

        if ( ! $toupdate) {
            $field = \databox_field::create($record->get_databox(), 'testBusiness' . mt_rand(), false);
            $field->set_business(true);
            $field->save();

            $options->allowBusinessFieldsOn(array($record->get_collection()));

            $toupdate[$field->get_id()] = array(
                'meta_id'        => null
                , 'meta_struct_id' => $field->get_id()
                , 'value'          => $query_string
            );
        }

        self::$searchEngine->setOptions($options);

        $record->set_metadatas($toupdate);

        self::$searchEngine->updateRecord($record);
        $this->updateIndex();

        $results = self::$searchEngine->query($query_string, 0, 1);

        $this->assertEquals(1, $results->total());

        self::$searchEngine->removeRecord($record);
        $this->updateIndex();
    }

    public function testBusinessFieldNotAvailable()
    {
        $record = self::$records['record_24'];

        self::$searchEngine->addRecord($record);
        $this->updateIndex();

        $query_string = 'boomboklot' . $record->get_record_id() . 'businessNotAvailable';

        $results = self::$searchEngine->query($query_string, 0, 1);
        $this->assertEquals(0, $results->total());

        $toupdate = array();

        foreach ($record->get_databox()->get_meta_structure()->get_elements() as $field) {

            if ( ! $field->isBusiness()) {
                continue;
            }

            try {
                $values = $record->get_caption()->get_field($field->get_name())->get_values();
                $value = array_pop($values);
                $meta_id = $value->getId();
            } catch (\Exception $e) {
                $meta_id = null;
            }

            $toupdate[$field->get_id()] = array(
                'meta_id'        => $meta_id
                , 'meta_struct_id' => $field->get_id()
                , 'value'          => $query_string
            );
            break;
        }

        if ( ! $toupdate) {
            $field = \databox_field::create($record->get_databox(), 'testBusiness' . mt_rand(), false);
            $field->set_business(true);
            $field->save();

            $toupdate[$field->get_id()] = array(
                'meta_id'        => null
                , 'meta_struct_id' => $field->get_id()
                , 'value'          => $query_string
            );
        }

        $record->set_metadatas($toupdate);

        self::$searchEngine->updateRecord($record);
        $this->updateIndex();

        $results = self::$searchEngine->query($query_string, 0, 1);

        $this->assertEquals(0, $results->total());

        self::$searchEngine->removeRecord($record);
        $this->updateIndex();
    }

    public function testUpdateQueryOnEmptyField()
    {
        $appbox = \appbox::get_instance(\bootstrap::getCore());
        foreach ($appbox->get_databoxes() as $databox) {
            break;
        }
        $options = new SearchEngineOptions();
        $options->onCollections($databox->get_collections());
        $options->useStemming(true);
        $options->setLocale('en');

        $record = self::$records['record_24'];

        self::$searchEngine->addRecord($record);
        $this->updateIndex();

        $query_string = 'boomboklot' . $record->get_record_id() . 'anotherfield';

        $results = self::$searchEngine->query($query_string, 0, 1);
        $this->assertEquals(0, $results->total());

        $toupdate = array();

        foreach ($record->get_databox()->get_meta_structure()->get_elements() as $field) {
            try {
                $values = $record->get_caption()->get_field($field->get_name())->get_values();
                $value = array_pop($values);
                $meta_id = $value->getId();
            } catch (\Exception $e) {
                $meta_id = null;
            }

            if ( ! $toupdate) {
                $toupdate[$field->get_id()] = array(
                    'meta_id'        => $meta_id
                    , 'meta_struct_id' => $field->get_id()
                    , 'value'          => $query_string
                );
            } else {
                $options->setFields(array($field));

                break;
            }
        }

        self::$searchEngine->setOptions($options);

        $record->set_metadatas($toupdate);

        self::$searchEngine->updateRecord($record);
        $this->updateIndex();

        $results = self::$searchEngine->query($query_string, 0, 1);

        $this->assertEquals(0, $results->total());

        self::$searchEngine->removeRecord($record);
        $this->updateIndex();
    }

    public function testUpdateNonIndexableRecord()
    {
        $record = self::$records['record_24'];

        self::$searchEngine->addRecord($record);
        $this->updateIndex();

        $query_string = 'boomboklot_no_index_' . $record->get_record_id() . '_';

        $results = self::$searchEngine->query($query_string, 0, 1);
        $this->assertEquals(0, $results->total());

        $toupdate = array();

        foreach ($record->get_databox()->get_meta_structure()->get_elements() as $field) {
            if ($field->is_indexable()) {
                continue;
            }

            try {
                $values = $record->get_caption()->get_field($field->get_name())->get_values();
                $value = array_pop($values);
                $meta_id = $value->getId();
            } catch (\Exception $e) {
                $meta_id = null;
            }

            $toupdate[$field->get_id()] = array(
                'meta_id'        => $meta_id
                , 'meta_struct_id' => $field->get_id()
                , 'value'          => $query_string
            );
            break;
        }

        if ( ! $toupdate) {
            $this->markTestSkipped('No non-indexable field found');
        }

        $record->set_metadatas($toupdate);

        self::$searchEngine->updateRecord($record);
        $this->updateIndex();

        $results = self::$searchEngine->query($query_string, 0, 1);
        $this->assertEquals(0, $results->total());

        self::$searchEngine->removeRecord($record);
        $this->updateIndex();
    }

    public function testDeleteRecord()
    {
        $record = self::$records['record_24'];

        $results = self::$searchEngine->query('recordid=' . $record->get_record_id(), 0, 1);
        $floor = $results->total();

        self::$searchEngine->addRecord($record);
        $this->updateIndex();

        $results = self::$searchEngine->query('recordid=' . $record->get_record_id(), 0, 1);
        $this->assertEquals($floor + 1, $results->total());

        self::$searchEngine->removeRecord($record);
        $this->updateIndex();

        $results = self::$searchEngine->query('recordid=' . $record->get_record_id(), 0, 1);
        $this->assertEquals($floor, $results->total());
    }

    public function testAvailableTypes()
    {
        $this->assertTrue(is_array(self::$searchEngine->availableTypes()));
        foreach (self::$searchEngine->availableTypes() as $type) {
            $this->assertTrue(in_array($type, array(SearchEngineInterface::GEM_TYPE_ENTRY, SearchEngineInterface::GEM_TYPE_RECORD, SearchEngineInterface::GEM_TYPE_STORY)));
        }
    }

    public function testConfigurationPanel()
    {
        $this->assertInstanceOf('\\Alchemy\\Phrasea\\SearchEngine\\ConfigurationPanelInterface', self::$searchEngine->configurationPanel());
    }

    public function testStatus()
    {
        foreach (self::$searchEngine->status() as $StatusKeyValue) {
            $this->assertTrue(is_array($StatusKeyValue));
            $this->assertTrue(is_scalar($StatusKeyValue[0]));
            $this->assertTrue(is_scalar($StatusKeyValue[1]));
        }
    }

    public function testAddStory()
    {
        $story = self::$records['record_story_1'];

        $options = new SearchEngineOptions();
        $options->onCollections(self::$user->ACL()->get_granted_base());
        $options->setSearchType(SearchEngineOptions::RECORD_GROUPING);
        self::$searchEngine->setOptions($options);

        $results = self::$searchEngine->query('storyid=' . $story->get_record_id(), 0, 1);

        $this->assertEquals(0, $results->total());

        self::$searchEngine->addStory($story);
        $this->updateIndex();

        $results = self::$searchEngine->query('storyid=' . $story->get_record_id(), 0, 1);

        $this->assertEquals(1, $results->total());

        self::$searchEngine->removeStory($story);
        $this->updateIndex();
    }

    public function testUpdateStory()
    {
        $story = self::$records['record_story_1'];

        $options = new SearchEngineOptions();
        $options->onCollections(self::$user->ACL()->get_granted_base());
        $options->setSearchType(SearchEngineOptions::RECORD_GROUPING);
        self::$searchEngine->setOptions($options);

        self::$searchEngine->addStory($story);
        $this->updateIndex();

        $query_string = 'boomboklot_' . $story->get_record_id() . '_';

        $results = self::$searchEngine->query($query_string, 0, 1);
        $this->assertEquals(0, $results->total());

        $toupdate = array();

        foreach ($story->get_databox()->get_meta_structure()->get_elements() as $field) {
            try {
                $values = $story->get_caption()->get_field($field->get_name())->get_values();
                $value = array_pop($values);
                $meta_id = $value->getId();
            } catch (\Exception $e) {
                $meta_id = null;
            }

            $toupdate[$field->get_id()] = array(
                'meta_id'        => $meta_id
                , 'meta_struct_id' => $field->get_id()
                , 'value'          => $query_string
            );
            break;
        }

        $story->set_metadatas($toupdate);

        self::$searchEngine->updateStory($story);
        $this->updateIndex();

        $results = self::$searchEngine->query($query_string, 0, 1);
        $this->assertEquals(1, $results->total());

        self::$searchEngine->removeStory($story);
        $this->updateIndex();
    }

    public function testStatusQuery()
    {
        $appbox = \appbox::get_instance(\bootstrap::getCore());
        foreach ($appbox->get_databoxes() as $databox) {
            break;
        }
        $options = new SearchEngineOptions();
        $options->onCollections($databox->get_collections());

        $record = self::$records['record_24'];

        self::$searchEngine->addRecord($record);
        $this->updateIndex();

        $query_string = 'boomboklot' . $record->get_record_id() . 'statusQuery';

        $results = self::$searchEngine->query($query_string, 0, 1);
        $this->assertEquals(0, $results->total());

        $toupdate = array();

        foreach ($record->get_databox()->get_meta_structure()->get_elements() as $field) {
            try {
                $values = $record->get_caption()->get_field($field->get_name())->get_values();
                $value = array_pop($values);
                $meta_id = $value->getId();
            } catch (\Exception $e) {
                $meta_id = null;
            }

            $toupdate[$field->get_id()] = array(
                'meta_id'        => $meta_id
                , 'meta_struct_id' => $field->get_id()
                , 'value'          => $query_string
            );
            break;
        }

        self::$searchEngine->setOptions($options);

        $record->set_metadatas($toupdate);

        self::$searchEngine->updateRecord($record);
        $this->updateIndex();

        $results = self::$searchEngine->query($query_string, 0, 1);
        $this->assertEquals(1, $results->total());

        $options->setStatus(array(4 => array('on' => array($record->get_databox()->get_sbas_id()))));
        self::$searchEngine->setOptions($options);

        $results = self::$searchEngine->query($query_string, 0, 1);

        $this->assertEquals(0, $results->total());

        $record->set_binary_status('10000');
        self::$searchEngine->updateRecord($record);

        $results = self::$searchEngine->query($query_string, 0, 1);
        $this->assertEquals(1, $results->total());


        $options->setStatus(array(4 => array('off' => array($record->get_databox()->get_sbas_id()))));
        self::$searchEngine->setOptions($options);

        $results = self::$searchEngine->query($query_string, 0, 1);
        $this->assertEquals(0, $results->total());

        self::$searchEngine->removeRecord($record);
        $this->updateIndex();
    }

    public function testExcerptFromSimpleQuery()
    {
        $record = self::$records['record_24'];

        self::$searchEngine->addRecord($record);
        $this->updateIndex();

        $query_string = 'boomboklot' . $record->get_record_id() . 'excerptSimpleQuery';

        $results = self::$searchEngine->query($query_string, 0, 1);
        $this->assertEquals(0, $results->total());

        $toupdate = array();

        foreach ($record->get_databox()->get_meta_structure()->get_elements() as $field) {
            try {
                $values = $record->get_caption()->get_field($field->get_name())->get_values();
                $value = array_pop($values);
                $meta_id = $value->getId();
            } catch (\Exception $e) {
                $meta_id = null;
            }

            $toupdate[$field->get_id()] = array(
                'meta_id'        => $meta_id
                , 'meta_struct_id' => $field->get_id()
                , 'value'          => $query_string
            );
            break;
        }

        $record->set_metadatas($toupdate);

        self::$searchEngine->updateRecord($record);
        $this->updateIndex();

        $results = self::$searchEngine->query($query_string, 0, 1);

        $this->assertEquals(1, $results->total());


        $record = $results->results()->first();

        $fields = array();

        foreach ($record->get_caption()->get_fields() as $field) {
            $fields[$field->get_name()] = array(
                'value'     => $field->get_serialized_values()
                , 'separator' => ';'
            );
        }

        $found = false;
        foreach (self::$searchEngine->excerpt($query_string, $fields, $record) as $field) {
            if (strpos($field, '<em>') !== false && strpos($field, '</em>') !== false) {
                $found = true;
                break;
            }
        }

        self::$searchEngine->removeRecord($record);
        $this->updateIndex();

        if ( ! $found) {
            $this->fail('Unable to build the excerpt');
        }
    }

    abstract function testAutocomplete();
}
