<?php

namespace Alchemy\Tests\Phrasea\SearchEngine;

use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;
use Symfony\Component\HttpFoundation\Request;

class SearchEngineOptionsTest extends \PhraseanetTestCase
{
    /**
     * @covers Alchemy\Phrasea\SearchEngine\SearchEngineOptions
     */
    public function testSerialize()
    {
        $options = new SearchEngineOptions(self::$DI['app']);
        $options->onCollections([self::$DI['collection']]);

        $options->allowBusinessFieldsOn([self::$DI['collection']]);

        foreach (self::$DI['collection']->get_databox()->get_meta_structure() as $field) {
            $options->setFields([$field]);
            $options->setDateFields([$field]);
            break;
        }

        $min_date = new \DateTime('-5 days');
        $max_date = new \DateTime('+5 days');

        $options->setMinDate(\DateTime::createFromFormat(DATE_ATOM, $min_date->format(DATE_ATOM)));
        $options->setMaxDate(\DateTime::createFromFormat(DATE_ATOM, $max_date->format(DATE_ATOM)));

        $serialized = $options->serialize();

        $this->assertEquals($options, SearchEngineOptions::hydrate(self::$DI['app'], $serialized));
    }

    /**
     * @covers Alchemy\Phrasea\SearchEngine\SearchEngineOptions::fromRequest
     */
    public function testFromRequest()
    {
        $this->authenticate(self::$DI['app']);

        foreach ($this->provideRequestData() as $pack) {
            list ($query, $request, $field, $dateField) = $pack;

            $httpRequest = new Request($query, $request);

            $options = SearchEngineOptions::fromRequest(self::$DI['app'], $httpRequest);

            // Check done this way because returned array can be indexed differently
            $collections = $options->getCollections();
            $this->assertCount(1, $collections);
            $this->assertContains(self::$DI['collection'], $collections);
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

            $httpRequest = new Request($query, $request);

            $options = SearchEngineOptions::fromRequest(self::$DI['app'], $httpRequest);

            $this->assertEquals([], $options->getCollections());
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

        $this->assertEquals([], $options->getCollections());
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

        foreach (self::$DI['collection']->get_databox()->get_meta_structure() as $db_field) {
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
            'bases' => [self::$DI['collection']->get_base_id()],
            'status' => ['4' => ['on' => [self::$DI['collection']->get_databox()->get_sbas_id()]]],
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

        $dataWithoutBases = $data;
        unset($dataWithoutBases['bases']);

        return [
            [[], $data, $field, $dateField],
            [$data, [], $field, $dateField],
        ];
    }
}
