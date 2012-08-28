<?php

namespace Alchemy\Phrasea\SearchEngine;

require_once __DIR__ . '/../../../PhraseanetPHPUnitAuthenticatedAbstract.class.inc';

abstract class SearchEngineAbstractTest extends \PhraseanetPHPUnitAbstract
{
    protected static $searchEngine;

    public function setUp()
    {
        parent::setUp();
        $appbox = \appbox::get_instance(\bootstrap::getCore());
        foreach($appbox->get_databoxes() as $databox) {
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

        $query_string = 'boomboklot' . $record->get_record_id() ;

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

        sleep(1);

        $results = self::$searchEngine->query($query_string, 0, 1);

        $this->assertEquals(1, $results->total());

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

    abstract function testAutocomplete();

}
