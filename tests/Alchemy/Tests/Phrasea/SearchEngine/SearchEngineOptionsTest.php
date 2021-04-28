<?php

namespace Alchemy\Tests\Phrasea\SearchEngine;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Collection\Reference\CollectionReference;
use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;
use Symfony\Component\HttpFoundation\Request;

/**
 * @group functional
 * @group legacy
 */
class SearchEngineOptionsTest extends \PhraseanetTestCase
{
    /**
     * @covers Alchemy\Phrasea\SearchEngine\SearchEngineOptions
     */
    public function testSerialize()
    {
        /** @var Application $app */
        $app = self::$DI['app'];
        /** @var \collection $collection */
        $collection = $this->getCollection();

        $options = new SearchEngineOptions(self::$DI['app']['repo.collection-references']);
        $options->onBasesIds([$collection->get_base_id()]);
        $options->setRecordType(SearchEngineOptions::TYPE_ALL);
        $options->setSearchType(SearchEngineOptions::RECORD_RECORD);
        $options->allowBusinessFieldsOn([$collection->get_base_id()]);

        foreach ($collection->get_databox()->get_meta_structure() as $field) {
            $options->setFields([$field]);
            $options->setDateFields([$field]);
            break;
        }

        $min_date = new \DateTime('-5 days');
        $max_date = new \DateTime('+5 days');
        $options->setMinDate(\DateTime::createFromFormat(DATE_ATOM, $min_date->format(DATE_ATOM)));
        $options->setMaxDate(\DateTime::createFromFormat(DATE_ATOM, $max_date->format(DATE_ATOM)));

        $options->setFirstResult(3);
        $options->setMaxResults(42);

        $serialized = $options->serialize();

        $this->assertEquals($options, SearchEngineOptions::hydrate($app, $serialized));
    }

    /**
     * @covers Alchemy\Phrasea\SearchEngine\SearchEngineOptions::fromRequest
     */
    public function testFromRequest()
    {
        /** @var Application $app */
        $app = self::$DI['app'];
        $this->authenticate($app);

        /** @var \collection $collection */
        $collection = self::$DI['collection'];
        $sbid = $collection->get_sbas_id();
        foreach ($this->provideRequestData() as $pack) {
            list ($query, $request, $field, $dateField) = $pack;

            $httpRequest = new Request($query, $request);

            $options = SearchEngineOptions::fromRequest($app, $httpRequest);

            // Check done this way because returned array can be indexed differently
            $collectionsReferences = $options->getCollectionsReferencesByDatabox();
            $this->assertCount(1, $collectionsReferences);
            $this->assertCount(1, $collectionsReferences[$sbid]);
            /** @var CollectionReference $collRef */
            $collRef = $collectionsReferences[$sbid][0];
            $this->assertEquals($collection->get_base_id(), $collRef->getBaseId());
            $this->assertEquals([$field], $options->getFields());
            $this->assertEquals('video', $options->getRecordType());
            $this->assertEquals('1', $options->getSearchType());
            $this->assertEquals('2012/12/21', $options->getMaxDate()->format('Y/m/d'));
            $this->assertEquals('2009/04/24', $options->getMinDate()->format('Y/m/d'));
            $this->assertEquals([$dateField], $options->getDateFields());
            $this->assertEquals('asc', $options->getSortOrder());
            $this->assertEquals('topinambour', $options->getSortBy());
            $this->assertEquals(true, $options->isStemmed());
        }
    }

    /**
     * @covers Alchemy\Phrasea\SearchEngine\SearchEngineOptions::fromRequest
     */
    public function testFromRequestUnauthenticated()
    {
        foreach ($this->provideRequestData() as $pack) {
            list ($query, $request, $field, $dateField) = $pack;

            // Do not request a specific collection as there are no access defined and request is then invalid
            if (isset($query['bases'])) {
                $query['bases'] = [];
            }
            if (isset($request['bases'])) {
                $request['bases'] = [];
            }
            $httpRequest = new Request($query, $request);

            $options = SearchEngineOptions::fromRequest(self::$DI['app'], $httpRequest);

            $this->assertEquals([], $options->getCollectionsReferencesByDatabox());
            $this->assertEquals([], $options->getFields());
            $this->assertEquals('video', $options->getRecordType());
            $this->assertEquals('1', $options->getSearchType());
            $this->assertEquals('2012/12/21', $options->getMaxDate()->format('Y/m/d'));
            $this->assertEquals('2009/04/24', $options->getMinDate()->format('Y/m/d'));
            $this->assertEquals([], $options->getDateFields());
            $this->assertEquals('asc', $options->getSortOrder());
            $this->assertEquals('topinambour', $options->getSortBy());
            $this->assertEquals(true, $options->isStemmed());
        }
    }

    /**
     * @covers Alchemy\Phrasea\SearchEngine\SearchEngineOptions::fromRequest
     */
    public function testFromRequestEmptyUnauthenticated()
    {
        $options = SearchEngineOptions::fromRequest(self::$DI['app'], new Request());

        $this->assertEquals([], $options->getCollectionsReferencesByDatabox());
        $this->assertEquals([], $options->getFields());
        $this->assertEquals(null, $options->getRecordType());
        $this->assertEquals('0', $options->getSearchType());
        $this->assertEquals(null, $options->getMaxDate());
        $this->assertEquals(null, $options->getMinDate());
        $this->assertEquals([], $options->getDateFields());
        $this->assertEquals('desc', $options->getSortOrder());
        $this->assertEquals(null, $options->getSortBy());
        $this->assertEquals(false, $options->isStemmed());
    }

    private function provideRequestData()
    {
        $field = $dateField = null;

        /** @var \collection $collection */
        $collection = self::$DI['collection'];
        foreach ($collection->get_databox()->get_meta_structure() as $db_field) {
            if (!$field) {
                $field = $db_field;
            } elseif (!$dateField) {
                $dateField = $db_field;
            } else {
                break;
            }
        }

        if (!$field || !$dateField) {
            $this->fail('Unable to get a field');
        }

        $data = [
            'bases' => [$collection->get_base_id()],
            'status' => ['4' => ['on' => [$collection->get_databox()->get_sbas_id()]]],
            'fields' => [$field->get_name()],
            'record_type' => 'video',
            'search_type' => '1',
            'date_min' => '2009/04/24',
            'date_max' => '2012/12/21',
            'date_field' => $dateField->get_name(),
            'ord' => 'asc',
            'sort' => 'topinambour',
            'stemme' => 'true',
        ];

        return [
            [[], $data, $field, $dateField],
            [$data, [], $field, $dateField],
        ];
    }
}
