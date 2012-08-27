<?php

namespace Alchemy\Phrasea\SearchEngine;

require_once __DIR__ . '/../../../PhraseanetPHPUnitAuthenticatedAbstract.class.inc';

abstract class SearchEngineAbstractTest extends \PhraseanetPHPUnitAuthenticatedAbstract
{

    protected static $searchEngine;

    public function setUp()
    {
        parent::setUp();

        if(!self::$searchEngine instanceof SearchEngineInterface) {
            $this->markTestSkipped('Unable to initialize search Engine');
        }

        $options = new SearchEngineOptions();
        $options->onCollections(self::$user->ACL()->get_granted_base());

        self::$searchEngine->setOptions($options);
    }

    protected function updateIndex()
    {
        return $this;
    }

    public function testAddRecord()
    {
        $record = self::$records['record_24'];

        $results = self::$searchEngine->query('recordid=' . $record->get_record_id(), 0, 1);

        $this->assertEquals(0, $results->total());

        self::$searchEngine->addRecord($record);
        $this->updateIndex();
        sleep(1);
        self::$searchEngine->query('recordid=' . $record->get_record_id(), 0, 1);

        $results = self::$searchEngine->query('recordid=' . $record->get_record_id(), 0, 1);

        $this->assertEquals(1, $results->total());
    }


//
//    public function testUpdateRecord()
//    {
//        $record = self::$records['record_24'];
//
//        self::$searchEngine->addRecord($record);
//        $this->updateIndex();
//
//        $results = self::$searchEngine->query('boomboklot', 0, 1);
//        $this->assertEquals(0, $results->total());
//
//        $toupdate = array();
//
//        foreach ($record->get_databox()->get_meta_structure()->get_elements() as $field) {
//            try {
//                $values = $record->get_caption()->get_field($field->get_name())->get_values();
//                $value = array_pop($values);
//                $meta_id = $value->getId();
//            } catch (\Exception $e) {
//                $meta_id = null;
//            }
//
//            $toupdate[$field->get_id()] = array(
//                'meta_id'        => $meta_id
//                , 'meta_struct_id' => $field->get_id()
//                , 'value'          => 'boomboklot '
//            );
//            break;
//        }
//
//        $record->set_metadatas($toupdate);
//
//        self::$searchEngine->updateRecord($record);
//        $this->updateIndex();
//
//        $results = self::$searchEngine->query('boomboklot', 0, 1);
//        $this->assertEquals(1, $results->total());
//    }
//
//    public function testDeleteRecord()
//    {
//        $record = self::$records['record_24'];
//
//        self::$searchEngine->addRecord($record);
//        $this->updateIndex();
//
//        $results = self::$searchEngine->query('recordid='.$record->get_record_id(), 0, 1);
//        $this->assertEquals(1, $results->total());
//
//        self::$searchEngine->removeRecord($record);
//        $this->updateIndex();
//
//        $results = self::$searchEngine->query('recordid='.$record->get_record_id(), 0, 1);
//        $this->assertEquals(0, $results->total());
//    }

}
