<?php

namespace Alchemy\Tests\Phrasea\SearchEngine;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;
use Alchemy\Phrasea\SearchEngine\SearchEngineInterface;

abstract class SearchEngineAbstractTest extends \PhraseanetPHPUnitAuthenticatedAbstract
{
    protected  $options;
    protected static $searchEngine;
    protected static $initialized = false;

    public function setUp()
    {
        parent::setUp();

        if (!self::$initialized) {
            $found = false;
            foreach (self::$DI['record_24']->get_databox()->get_meta_structure()->get_elements() as $field) {
                if (!$field->isBusiness()) {
                    continue;
                }
                $found = true;
            }

            if (!$found) {
                $field = \databox_field::create(self::$DI['app'], self::$DI['record_24']->get_databox(), 'testBusiness' . mt_rand(), false);
                $field->set_business(true);
                $field->save();
            }

            foreach (self::$DI['app']['phraseanet.appbox']->get_databoxes() as $databox) {
                break;
            }
        }

        $this->initialize();

        if (!self::$searchEngine instanceof SearchEngineInterface) {
            $this->markTestSkipped('Unable to initialize search Engine');
        }

        $options = new SearchEngineOptions();
        $options->onCollections($databox->get_collections());

        $this->options = $options;

        self::$searchEngine->setOptions($options);
    }

    /**
     * @return SearchEngineOptions
     */
    private function getOptions()
    {
        return $this->options;
    }

    public function testQueryRecordId()
    {
        $record = self::$DI['record_24'];
        $query_string = 'recordid=' . $record->get_record_id();

        self::$searchEngine->addRecord($record);
        $this->updateIndex();

        self::$searchEngine->resetCache();
        $results = self::$searchEngine->query($query_string, 0, 1);
        $this->assertEquals(1, $results->getTotal());

        $result = $results->getResults()->first();

        $this->assertEquals($record->get_record_id(), $result->get_record_id());
        $this->assertEquals($record->get_sbas_id(), $result->get_sbas_id());
    }

    public function testQueryStoryId()
    {
        $record = self::$DI['record_24'];
        $query_string = 'storyid=' . $record->get_record_id();

        self::$searchEngine->addRecord($record);
        $this->updateIndex();

        self::$searchEngine->resetCache();
        $results = self::$searchEngine->query($query_string, 0, 1);
        $this->assertEquals(1, $results->getTotal());

        $result = $results->getResults()->first();

        $this->assertEquals($record->get_record_id(), $result->get_record_id());
        $this->assertEquals($record->get_sbas_id(), $result->get_sbas_id());
    }

    public function testQueryByDateMin()
    {
        $record = self::$DI['record_24'];
        $query_string = 'boomboklot' . $record->get_record_id() . 'dateMin';

        $this->editRecord($query_string, $record);

        $date_field = $this->editDateRecord('2012-12-21 12:12:00', $record);

        if (!$date_field) {
            $this->markTestSkipped('unable to add a date to record');
        }

        self::$searchEngine->addRecord($record);
        $this->updateIndex();

        $options = $this->getOptions();
        $options->setDateFields(array($date_field));
        $options->setMinDate(\DateTime::createFromFormat('Y-m-d H:i:s', '2012-12-23 01:01:00'));
        self::$searchEngine->setOptions($options);

        self::$searchEngine->resetCache();
        $results = self::$searchEngine->query($query_string, 0, 1);
        $this->assertEquals(0, $results->getTotal());

        $options->setMinDate(\DateTime::createFromFormat('Y-m-d H:i:s', '2012-12-20 01:01:00'));
        self::$searchEngine->setOptions($options);

        self::$searchEngine->resetCache();
        $results = self::$searchEngine->query($query_string, 0, 1);
        $this->assertEquals(1, $results->getTotal());
    }

    private function editDateRecord($date, \record_adapter $record)
    {
        $date_field = null;

        foreach ($record->get_databox()->get_meta_structure() as $databox_field) {
            if ($databox_field->get_type() != \databox_field::TYPE_DATE) {
                continue;
            }

            $date_field = $databox_field;

            break;
        }

        if ($date_field) {

            $toupdate = array();

            try {
                $values = $record->get_caption()->get_field($databox_field->get_name())->get_values();
                $value = array_pop($values);
                $meta_id = $value->getId();
            } catch (\Exception $e) {
                $meta_id = null;
            }

            $toupdate[$databox_field->get_id()] = array(
                'meta_id'        => $meta_id
                , 'meta_struct_id' => $databox_field->get_id()
                , 'value'          => $date
            );

            $record->set_metadatas($toupdate);
        }


        return $date_field;
    }

    public function testQueryByDateMax()
    {
        $record = self::$DI['record_24'];
        $query_string = 'boomboklot' . $record->get_record_id() . 'dateMax';

        $this->editRecord($query_string, $record);

        $date_field = $this->editDateRecord('2012-12-21 12:12:00', $record);

        if (!$date_field) {
            $this->markTestSkipped('unable to add a date to record');
        }

        self::$searchEngine->addRecord($record);
        $this->updateIndex();

        $options = $this->getOptions();
        $options->setDateFields(array($date_field));
        $options->setMaxDate(\DateTime::createFromFormat('Y-m-d H:i:s', '2012-12-20 01:01:00'));
        self::$searchEngine->setOptions($options);

        self::$searchEngine->resetCache();
        $results = self::$searchEngine->query($query_string, 0, 1);
        $this->assertEquals(0, $results->getTotal());

        $options->setMaxDate(\DateTime::createFromFormat('Y-m-d H:i:s', '2012-12-23 01:01:00'));
        self::$searchEngine->setOptions($options);

        self::$searchEngine->resetCache();
        $results = self::$searchEngine->query($query_string, 0, 1);
        $this->assertEquals(1, $results->getTotal());
    }

    public function testQueryByDateRange()
    {
        $record = self::$DI['record_24'];
        $query_string = 'boomboklot' . $record->get_record_id() . 'dateRange';

        $this->editRecord($query_string, $record);

        $date_field = $this->editDateRecord('2012-12-21 12:12:00', $record);

        if (!$date_field) {
            $this->markTestSkipped('unable to add a date to record');
        }

        self::$searchEngine->addRecord($record);
        $this->updateIndex();

        $options = $this->getOptions();
        $options->setDateFields(array($date_field));
        $options->setMinDate(\DateTime::createFromFormat('Y-m-d H:i:s', '2012-12-18 01:01:00'));
        $options->setMaxDate(\DateTime::createFromFormat('Y-m-d H:i:s', '2012-12-20 01:01:00'));
        self::$searchEngine->setOptions($options);

        self::$searchEngine->resetCache();
        $results = self::$searchEngine->query($query_string, 0, 1);
        $this->assertEquals(0, $results->getTotal());

        $options->setMaxDate(\DateTime::createFromFormat('Y-m-d H:i:s', '2012-12-22 01:01:00'));
        self::$searchEngine->setOptions($options);

        self::$searchEngine->resetCache();
        $results = self::$searchEngine->query($query_string, 0, 1);
        $this->assertEquals(1, $results->getTotal());
    }

    protected function editRecord($string2add, \record_adapter &$record, $indexable = true, $business = false)
    {
        $toupdate = array();
        $field = null;

        foreach ($record->get_databox()->get_meta_structure()->get_elements() as $field) {

            if ($indexable !== $field->is_indexable() || $field->isBusiness() !== $business) {
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
                , 'value'          => $string2add
            );
            break;
        }

        $record->set_metadatas($toupdate);

        return $field;
    }

    public function testRecordNotIndexed()
    {
        $record = self::$DI['record_24'];
        $query_string = 'boomboklot' . $record->get_record_id() . 'defaultNotIndexed';

        $this->editRecord($query_string, $record);

        self::$searchEngine->resetCache();
        $results = self::$searchEngine->query($query_string, 0, 1);
        $this->assertEquals(0, $results->getTotal());
    }

    public function testAddRecord()
    {
        $record = self::$DI['record_24'];
        $query_string = 'boomboklot' . $record->get_record_id() . 'defaultAdd';

        $this->editRecord($query_string, $record);

        self::$searchEngine->addRecord($record);
        $this->updateIndex();

        self::$searchEngine->resetCache();
        $results = self::$searchEngine->query($query_string, 0, 1);
        $this->assertEquals(1, $results->getTotal());
    }

    public function testUpdateRecord()
    {
        $record = self::$DI['record_24'];

        self::$searchEngine->addRecord($record);
        $this->updateIndex();

        $query_string = 'boomboklot' . $record->get_record_id() . 'updateRecord';

        $this->editRecord($query_string, $record);

        self::$searchEngine->updateRecord($record);
        $this->updateIndex();

        self::$searchEngine->resetCache();
        $results = self::$searchEngine->query($query_string, 0, 1);
        $this->assertEquals(1, $results->getTotal());
    }

    protected function getDefaultOptions()
    {
        $appbox = self::$DI['app']['phraseanet.appbox'];
        foreach ($appbox->get_databoxes() as $databox) {
            break;
        }
        $options = new SearchEngineOptions();
        $options->onCollections($databox->get_collections());

        return $options;
    }

    /**
     * @dataProvider provideStemmData
     */
    public function testUpdateRecordWithStemm($language, $word, $stemm)
    {
        if (!self::$searchEngine->hasStemming()) {
            $this->markTestSkipped(sprintf(
                '%s does not support stemm, passing stemmatization for language %s',
                get_class(self::$searchEngine),
                $language
            ));
        }

        $options = $this->getDefaultOptions();
        $options->setStemming(true);
        $options->setLocale($language);
        self::$searchEngine->setOptions($options);

        $record = self::$DI['record_24'];
        $index_string = sprintf(
            'boomboklot%dstemmed%s %s',
            $record->get_record_id(),
            $language,
            $word
        );
        $query_string = sprintf(
            'boomboklot%dstemmed%s %s',
            $record->get_record_id(),
            $language,
            $stemm
        );

        $this->editRecord($index_string, $record);

        self::$searchEngine->addRecord($record);
        $this->updateIndex(array($language));

        self::$searchEngine->resetCache();
        $results = self::$searchEngine->query($query_string, 0, 1);
        $this->assertEquals(1, $results->getTotal());
    }

    public function provideStemmData()
    {
        $data = array();

        $examples = array(
            'fr' => array('word' => 'chevaux', 'stemm' => 'cheval'),
            'en' => array('word' => 'consistency', 'stemm' => 'consistent'),
            'de' => array('word' => 'aufeinanderfolgender', 'stemm' => 'aufeinanderfolg'),
            'nl' => array('word' => 'lichamelijk', 'stemm' => 'licham'),
        );

        foreach (Application::getAvailableLanguages() as $language => $name) {

            $codes = explode('_', $language);
            $languageCode = $codes[0];

            if (!isset($examples[$languageCode])) {
                $this->fail(sprintf('Missing stemm examples for language %s', $languageCode));
            }

            $data[] = array(
                $languageCode,
                $examples[$languageCode]['word'],
                $examples[$languageCode]['stemm'],
            );
        }

        return $data;
    }

    public function testUpdateQueryOnField()
    {
        $options = $this->getDefaultOptions();
        $record = self::$DI['record_24'];

        $query_string = 'boomboklot' . $record->get_record_id() . 'onfield';

        $field = $this->editRecord($query_string, $record);
        $options->setFields(array($field));

        self::$searchEngine->setOptions($options);

        self::$searchEngine->addRecord($record);
        $this->updateIndex();

        self::$searchEngine->resetCache();
        $results = self::$searchEngine->query($query_string, 0, 1);
        $this->assertEquals(1, $results->getTotal());
    }

    public function testBusinessFieldAvailable()
    {
        $options = $this->getDefaultOptions();
        $record = self::$DI['record_24'];

        $query_string = 'boomboklot' . $record->get_record_id() . 'businessAvailable';

        $this->editRecord($query_string, $record, true, true);
        $options->allowBusinessFieldsOn(array($record->get_collection()));
        self::$searchEngine->setOptions($options);

        self::$searchEngine->addRecord($record);
        $this->updateIndex();

        self::$searchEngine->resetCache();
        $results = self::$searchEngine->query($query_string, 0, 1);
        $this->assertEquals(1, $results->getTotal());
    }

    public function testBusinessFieldNotAvailable()
    {
        $record = self::$DI['record_24'];
        $query_string = 'boomboklot' . $record->get_record_id() . 'businessNotAvailable';

        $this->editRecord($query_string, $record, true, true);

        self::$searchEngine->addRecord($record);
        $this->updateIndex();

        self::$searchEngine->resetCache();
        $results = self::$searchEngine->query($query_string, 0, 1);
        $this->assertEquals(0, $results->getTotal());
    }

    public function testUpdateQueryOnEmptyField()
    {
        $options = $this->getDefaultOptions();

        $record = self::$DI['record_24'];
        $query_string = 'boomboklot' . $record->get_record_id() . 'anotherfield';

        $selectedField = $this->editRecord($query_string, $record);

        foreach ($record->get_databox()->get_meta_structure()->get_elements() as $field) {
            if ($selectedField->get_id() != $field->get_id()) {
                $options->setFields(array($field));

                break;
            }
        }

        self::$searchEngine->addRecord($record);
        $this->updateIndex();

        self::$searchEngine->setOptions($options);
        self::$searchEngine->resetCache();
        $results = self::$searchEngine->query($query_string, 0, 1);
        $this->assertEquals(0, $results->getTotal());
    }

    public function testUpdateNonIndexableRecord()
    {
        $record = self::$DI['record_24'];
        $query_string = 'boomboklot_no_index_' . $record->get_record_id() . '_';

        $field = $this->editRecord($query_string, $record, false);
        if (!$field) {
            $this->markTestSkipped('No non-indexable field found');
        }

        self::$searchEngine->addRecord($record);
        $this->updateIndex();

        self::$searchEngine->resetCache();
        $results = self::$searchEngine->query($query_string, 0, 1);
        $this->assertEquals(0, $results->getTotal());
    }

    public function testDeleteRecord()
    {
        $record = self::$DI['record_24'];
        $query_string = 'boomboklot' . $record->get_record_id() . 'deleteRecord';

        $this->editRecord($query_string, $record);

        self::$searchEngine->addRecord($record);
        $this->updateIndex();
        self::$searchEngine->removeRecord($record);
        $this->updateIndex();

        self::$searchEngine->resetCache();
        $results = self::$searchEngine->query($query_string, 0, 1);
        $this->assertEquals(0, $results->getTotal());
    }

    public function testAvailableTypes()
    {
        $this->assertTrue(is_array(self::$searchEngine->getAvailableTypes()));
        foreach (self::$searchEngine->getAvailableTypes() as $type) {
            $this->assertTrue(in_array($type, array(SearchEngineInterface::GEM_TYPE_ENTRY, SearchEngineInterface::GEM_TYPE_RECORD, SearchEngineInterface::GEM_TYPE_STORY)));
        }
    }

    public function testConfigurationPanel()
    {
        $this->assertInstanceOf('\\Alchemy\\Phrasea\\SearchEngine\\ConfigurationPanelInterface', self::$searchEngine->getConfigurationPanel());
    }

    public function testStatus()
    {
        foreach (self::$searchEngine->getStatus() as $StatusKeyValue) {
            $this->assertTrue(is_array($StatusKeyValue));
            $this->assertTrue(is_scalar($StatusKeyValue[0]));
            $this->assertTrue(is_scalar($StatusKeyValue[1]));
        }
    }

    public function testAddStory()
    {
        $story = self::$DI['record_story_1'];
        $query_string = 'story' . $story->get_record_id() . 'addStory';

        $options = $this->getDefaultOptions();
        $options->setSearchType(SearchEngineOptions::RECORD_GROUPING);

        self::$searchEngine->setOptions($options);

        $this->editRecord($query_string, $story);

        self::$searchEngine->addStory($story);
        $this->updateIndex();

        self::$searchEngine->resetCache();
        $results = self::$searchEngine->query($query_string, 0, 1);
        $this->assertEquals(1, $results->getTotal());
    }

    public function testUpdateStory()
    {
        $story = self::$DI['record_story_1'];

        $options = $this->getDefaultOptions();
        $options->setSearchType(SearchEngineOptions::RECORD_GROUPING);

        self::$searchEngine->setOptions($options);

        self::$searchEngine->addStory($story);
        $this->updateIndex();

        $query_string = 'story' . $story->get_record_id() . 'updateStory';
        $this->editRecord($query_string, $story);

        self::$searchEngine->updateStory($story);
        $this->updateIndex();

        self::$searchEngine->resetCache();
        $results = self::$searchEngine->query($query_string, 0, 1);
        $this->assertEquals(1, $results->getTotal());
    }

    public function testStatusQueryOnOverOff()
    {
        $options = $this->getDefaultOptions();
        $record = self::$DI['record_24'];
        $record->set_binary_status('00000');

        $query_string = 'boomboklot' . $record->get_record_id() . 'statusQueryOff';
        $this->editRecord($query_string, $record);

        self::$searchEngine->addRecord($record);
        $this->updateIndex();

        $options->setStatus(array(4 => array('on' => array($record->get_databox()->get_sbas_id()))));
        self::$searchEngine->setOptions($options);

        self::$searchEngine->resetCache();
        $results = self::$searchEngine->query($query_string, 0, 1);
        $this->assertEquals(0, $results->getTotal());
    }

    public function testStatusQueryOnOverOn()
    {
        $options = $this->getDefaultOptions();

        $record = self::$DI['record_24'];
        $record->set_binary_status('10000');

        $options->setStatus(array(4 => array('on' => array($record->get_databox()->get_sbas_id()))));
        self::$searchEngine->setOptions($options);

        $query_string = 'boomboklot' . $record->get_record_id() . 'statusQueryOnOverOn';
        $this->editRecord($query_string, $record);

        self::$searchEngine->addRecord($record);
        $this->updateIndex();

        self::$searchEngine->resetCache();
        $results = self::$searchEngine->query($query_string, 0, 1);
        $this->assertEquals(1, $results->getTotal());
    }

    public function testStatusQueryOffOverOn()
    {
        $options = $this->getDefaultOptions();

        $record = self::$DI['record_24'];
        $record->set_binary_status('10000');

        $options->setStatus(array(4 => array('off' => array($record->get_databox()->get_sbas_id()))));
        self::$searchEngine->setOptions($options);

        $query_string = 'boomboklot' . $record->get_record_id() . 'statusQueryOff';
        $this->editRecord($query_string, $record);

        self::$searchEngine->addRecord($record);
        $this->updateIndex();

        self::$searchEngine->resetCache();
        $results = self::$searchEngine->query($query_string, 0, 1);
        $this->assertEquals(0, $results->getTotal());
    }

    public function testStatusQueryOffOverOff()
    {
        $options = $this->getDefaultOptions();

        $record = self::$DI['record_24'];
        $record->set_binary_status('00000');

        $options->setStatus(array(4 => array('off' => array($record->get_databox()->get_sbas_id()))));
        self::$searchEngine->setOptions($options);

        $query_string = 'boomboklot' . $record->get_record_id() . 'statusQueryOff';
        $this->editRecord($query_string, $record);

        self::$searchEngine->addRecord($record);
        $this->updateIndex();

        self::$searchEngine->resetCache();
        $results = self::$searchEngine->query($query_string, 0, 1);
        $this->assertEquals(1, $results->getTotal());
    }

    public function testStatusQueryUpdate()
    {
        $options = $this->getDefaultOptions();
        $record = self::$DI['record_24'];
        $record->set_binary_status('00000');

        $query_string = 'boomboklot' . $record->get_record_id() . 'statusQueryUpdate';
        $this->editRecord($query_string, $record);

        self::$searchEngine->addRecord($record);
        $this->updateIndex();

        $options->setStatus(array(4 => array('on' => array($record->get_databox()->get_sbas_id()))));
        self::$searchEngine->setOptions($options);

        self::$searchEngine->resetCache();
        $results = self::$searchEngine->query($query_string, 0, 1);
        $this->assertEquals(0, $results->getTotal());

        $record->set_binary_status('10000');

        self::$searchEngine->updateRecord($record);
        $this->updateIndex();

        self::$searchEngine->resetCache();
        $results = self::$searchEngine->query($query_string, 0, 1);
        $this->assertEquals(1, $results->getTotal());
    }

    public function testExcerptFromSimpleQuery()
    {
        $record = self::$DI['record_24'];
        $query_string = 'boomboklot' . $record->get_record_id() . 'excerptSimpleQuery';

        $this->editRecord($query_string, $record);

        self::$searchEngine->addRecord($record);
        $this->updateIndex();

        self::$searchEngine->resetCache();
        $results = self::$searchEngine->query($query_string, 0, 1);
        $fields = array();
        $foundRecord = $results->getResults()->first();

        $this->assertInstanceOf('\record_adapter', $foundRecord);

        foreach ($foundRecord->get_caption()->get_fields() as $field) {
            $fields[$field->get_name()] = array(
                'value'     => $field->get_serialized_values()
                , 'separator' => ';'
            );
        }

        $found = false;
        foreach (self::$searchEngine->excerpt($query_string, $fields, $foundRecord) as $field) {
            if (strpos($field, '<em>') !== false && strpos($field, '</em>') !== false) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            $this->fail('Unable to build the excerpt');
        }
    }

    abstract public function initialize();

    abstract public function testAutocomplete();

    abstract protected function updateIndex(array $stemms = array());
}
